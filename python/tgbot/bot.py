import os
import asyncio
import logging
import textwrap
from bot_redis import RedisManager
from datetime import datetime
from aiogram import Bot, Dispatcher, types
from aiogram.filters import Command
from aiogram.fsm.state import State, StatesGroup
from aiogram.fsm.context import FSMContext
from aiogram.types import ReplyKeyboardMarkup, KeyboardButton
from dotenv import load_dotenv
from adds import adds
from gtables import GTables

# Загрузка переменных окружения
load_dotenv()

# Настройка логирования
logging.basicConfig(level=logging.INFO, filename="bot.log")
logger = logging.getLogger(__name__)

# Инициализация бота
BOT_TOKEN = os.getenv("BOT_TOKEN")
CHAT_ID = os.getenv("CHAT_ID")
bot = Bot(token=BOT_TOKEN)
dp = Dispatcher()

# Redis
redis_manager = RedisManager(db=1)

# Google Sheets
gsheet = GTables(
    os.getenv("SPREADSHEET_ID"),
    'Имя бота',
    ["ID", "Дата и время", "Клиент", "Телефон", "Комментарий"]
)

WARNING_CHOICE = 'Пожалуйста, выберите один из предложенных вариантов'

# ========== СОСТОЯНИЯ БОТА ==========

class Form(StatesGroup):
    waiting_for_prices = State()
    waiting_for_phone = State()
    waiting_for_comment = State()
    
# ========== КЛАВИАТУРЫ ==========

prices_choices = ['Прайс услуги']

def get_keyboard(values):
    return ReplyKeyboardMarkup(
        keyboard = [[KeyboardButton(text = el)] for el in values],
        resize_keyboard = True,
        one_time_keyboard = True
    )
    
# Клавиатура для отправки номера
def get_phone_keyboard():
    return ReplyKeyboardMarkup(
        keyboard = [
            [KeyboardButton(text = "📞 Отправить мой контакт", request_contact = True)]
            #[KeyboardButton(text = "↩️ Назад")]
        ],
        resize_keyboard = True,
        one_time_keyboard = True
    )

# ========== ОБРАБОТЧИКИ ==========

@dp.message(Command("start"))
async def cmd_start(message: types.Message, state: FSMContext):

    await message.answer(
        "Приветствие",
        parse_mode = "Markdown",
        reply_markup = get_keyboard(prices_choices)
    )
    
    # Устанавливаем состояние ожидания выбора
    await state.set_state(Form.waiting_for_prices)

@dp.message(Command("add_comment"))
async def cmd_add_comment(message: types.Message, state: FSMContext):

    user_id = message.from_user.id

    if not redis_manager.can_comment(user_id):
        await state.clear()
        await message.answer(
            "⚠️ Вы не сможете отправить комментарий, пока не оставите заявку.",
            reply_markup=types.ReplyKeyboardRemove()
        )
        return

    await message.answer(
        "Оставьте комментарий к последней заявке.",
        reply_markup=types.ReplyKeyboardRemove()
    )
    
    await state.set_state(Form.waiting_for_comment)

@dp.message(Form.waiting_for_prices)
async def process_prices(message: types.Message, state: FSMContext):
    
    if message.text not in prices_choices:
        await message.answer(WARNING_CHOICE)
        return

    await message.answer(get_prices_text(), parse_mode = 'HTML')

    await message.answer(
        "Для вызова мастера или получения консультации по стоимости, пожалуйста, укажите ваш адрес и номер телефона.",
        parse_mode = 'HTML',
        reply_markup = get_keyboard(adds['main'])
    )

    # Просим отправить номер телефона
    await message.answer(
        "Оставьте ваше имя и контактный номер телефона.",
        parse_mode = "Markdown",
        reply_markup = get_phone_keyboard()
    )

    await state.set_state(Form.waiting_for_phone)

@dp.message(Form.waiting_for_phone)
async def process_contact(message: types.Message, state: FSMContext):

    contact = message.contact

    if not contact or contact.user_id != message.from_user.id:
        await message.answer("❌ Пожалуйста, отправьте свой контакт")
        return
    
    client = []

    if contact.first_name:
        client.append(contact.first_name)

    if contact.last_name:
        client.append(contact.last_name)
    
    client = " ".join(client)
    order_id = redis_manager.get_next_id()

    success = gsheet.save([
        order_id,
        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        client,
        contact.phone_number
    ])

    if success:        
        await message.answer(
            "Спасибо, наш специалист свяжется с вами в ближайшее время для консультации.",
            parse_mode = "Markdown",
            reply_markup = types.ReplyKeyboardRemove()
        )

        redis_manager.set_user_order(contact.user_id, order_id)

        await reqByBot(f"Новая заявка #{order_id}\n<b>Клиент:</b> {client} {contact.phone_number}")
    else:
        await message.answer(
            "⚠️ Произошла ошибка при сохранении данных.\n"
            "Пожалуйста, попробуйте снова: /start",
            parse_mode = "Markdown", reply_markup = types.ReplyKeyboardRemove()
        )

    await state.set_state(Form.waiting_for_comment)

@dp.message(Form.waiting_for_comment)
async def process_add_comment(message: types.Message, state: FSMContext):

    user_id = message.from_user.id
    order_id = redis_manager.get_order_id(user_id)

    if not redis_manager.can_comment(user_id) or not order_id:
        await message.answer("Действие недоступно", reply_markup=types.ReplyKeyboardRemove())
        return
    
    if not gsheet.add_comment(order_id, message.text):
        await message.answer("Не получилось отправить комментарий", reply_markup=types.ReplyKeyboardRemove())
        return

    await message.answer("Сообщение отправлено", reply_markup=types.ReplyKeyboardRemove())
    await reqByBot(f"Сообщение по заявке #{order_id}:\n{message.text}")

# Request to Admin
async def reqByBot(msg):
    await bot.send_message(chat_id=CHAT_ID, text=msg, parse_mode="HTML")

# Setup all comands
async def setup_commands():
    commands = [
        types.BotCommand(command="start", description="🚀 Начать"),
        types.BotCommand(command="add_comment", description="📝 Добавить комментарий")
    ]

    await bot.set_my_commands(commands)

def get_prices_text():
    return textwrap.dedent("""
    ЧЕРНОВАЯ САНТЕХНИКА (МОНТАЖ КОММУНИКАЦИЙ)

    <b>Отопление:</b>
    • Точка отопления — N р.
    • Прокладка трассы отопления — N р.
    • Установка радиатора (на готовые выводы) — N р.
    """)

# ========== ЗАПУСК БОТА ==========

async def main():
    await setup_commands()

    # Проверяем подключение к Google Sheets
    if gsheet.is_connect():
        logger.info("✅ Подключение к Google Sheets успешно!")
        print("✅ Подключение к Google Sheets успешно!")
    else:
        logger.warning("⚠️ Google Sheets недоступен. Данные не будут сохраняться.")
        print("⚠️ Google Sheets недоступен. Данные не будут сохраняться.")
    
    print("Бот запущен! Нажмите Ctrl+C для остановки")

    await dp.start_polling(bot)

if __name__ == "__main__":
    asyncio.run(main())