from database.db_config import db
from database.models import PushTokens, Session
from loguru import logger
from config import push_api_key
import json
import os
import datetime as dt
from pyfcm import FCMNotification

push_service = FCMNotification(api_key=push_api_key)

#Вставить в базу данных
def insert_database(arg):
    db.session.add(arg)
    db.session.flush()
    db.session.commit()
        
@logger.catch
def createSession(user_id, token, device, os, app_version):
    try:
        datetime = dt.datetime.now()
        data = Session(user_id, token, device, os, app_version, datetime, datetime)
        insert_database(data)
        return data.id
    except Exception as e:
        db.session.rollback()
        return 0
        
@logger.catch
def removeSession(session_id):
    try:
        db.session.delete(Session.query.filter_by(id=session_id).first())
        db.session.commit()
        return True
    except Exception as e:
        db.session.rollback()
        return False

def check_updates(current_version):
    with open('version_apk.json', 'r', encoding='utf-8') as file:

        data = json.load(file)

        if float(current_version) < float(data['version']):
            return int(os.path.getsize('./database/app.apk'))
        else:
            return int(0)
    
@logger.catch
def send_event(user_id:int, title, message, data):
    try:
        tokens = [str(token) for token in PushTokens.query.filter(PushTokens.user_id == user_id).all()]

        logger.info('SEND EVENT TOKEN', tokens)
        logger.debug(f'Дата {data}')

        data_message = {"data": json.loads(data)}

        if tokens != None:
            result = push_service.notify_multiple_devices(
                registration_ids=tokens, 
                message_title=title, 
                message_body=message,
                data_message=data_message
            )
            
            logger.info(result)
        return
    except Exception as E:
        logger.error(f'SEND EVENT ERROR {E}')
        return
        
# Получить все FB-токены юзера
def get_user_tokens(id):
    return [str(token) for token in PushTokens.query.filter(PushTokens.user_id == id).all()]


# и другие методы
    