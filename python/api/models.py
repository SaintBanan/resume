from database.db_config import db

class PushTokens(db.Model):
    user_id = db.Column(db.Integer, primary_key = True)
    token = db.Column(db.String(255), primary_key = True)
    create_datetime = db.Column(db.DateTime(timezone=True))
    update_datetime = db.Column(db.DateTime(timezone=True))

    def __str__(self):
        return self.token
    
    def __repr__(self):
        return f"{self.user_id}:{self.token}"
