import gspread
import logging
from oauth2client.service_account import ServiceAccountCredentials

logger = logging.getLogger(__name__)

# Класс для работы с Google таблицами
class GTables:
    def __init__(self, spreadsheet_id, title, headers):
        try:
            if not spreadsheet_id:
                raise ValueError("SPREADSHEET_ID не указан")
            
            scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
            creds = ServiceAccountCredentials.from_json_keyfile_name('credentials.json', scope)
            client = gspread.authorize(creds)
            sheet = client.open_by_key(spreadsheet_id)

            self.sheet = None
            self.c_i = len(headers) - 1

            try:
                self.sheet = sheet.worksheet(title)
            except Exception as e:
                self.sheet = sheet.add_worksheet(title=title, rows=1000, cols=10)
                self.sheet.append_row(headers)
                logger.error(f"Создаем лист {title}")
                print(f"Создаем лист {title}")
        except Exception as e:
            logger.error(f"Ошибка подключения к Google Sheets: {e}")
            print(f"Ошибка подключения к Google Sheets: {e}")

    def is_connect(self):
        return self.sheet is not None
    
    def save(self, user_data):
        try:
            if not self.sheet: return False
            
            self.sheet.append_row(user_data)

            return True
        except Exception as e:
            logger.error(f"Ошибка сохранения в таблицу: {e}")
            print(f"Ошибка сохранения в таблицу: {e}")
            return False
        
    # Добавить коммент к записи
    def add_comment(self, id: str, comment: str):
        try:
            if not self.sheet: return False

            comment = comment.strip()

            if not comment: return True

            for i, row in enumerate(self.sheet.get_all_values()[1:], start=2):
                if row[0] == id:
                    self.sheet.update_cell(i, self.c_i + 1, comment)
                    break
            
            return True
        except Exception as e:
            logger.error(f"Ошибка добавления комментария в таблицу: {e}")
            print(f"Ошибка добавления комментария в таблицу: {e}")
            return False