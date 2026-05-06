import redis
import logging
from datetime import timedelta

logger = logging.getLogger(__name__)

class RedisManager:
    def __init__(self, db = 0, host = 'localhost', port = 6379, password = None):
        self.client = redis.Redis(
            host=host,
            port=port,
            db=db,
            password=password,
            decode_responses=True,
        )

        self._check_connection()
    
    def _check_connection(self):
        try:
            self.client.ping()
            print("✅ Redis подключен")
        except redis.ConnectionError:
            print("❌ Ошибка подключения к Redis")
            raise
        
    def get_next_id(self) -> int:
        return self.client.incr("id")

    def set_user_order(self, user_id: int, order_id: int):
        self.client.set(f"comment:{user_id}", str(order_id))

    def get_order_id(self, user_id: int):
        return self.client.get(f"comment:{user_id}")
    
    def can_comment(self, user_id: int) -> bool:
        return self.client.exists(f"comment:{user_id}")

    def open_comment_window(self, user_id: int, order_id: int, hours: int = 24):
        self.client.setex(
            f"comment_window:{user_id}", timedelta(hours=hours), str(order_id)
        )

    def close_comment_window(self, user_id: int):
        self.client.delete(f"comment_window:{user_id}")