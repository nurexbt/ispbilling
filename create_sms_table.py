import mysql.connector

db = mysql.connector.connect(host='127.0.0.1', user='root', password='', database='billsoft_db', port=3307)
cursor = db.cursor()

sql = """
CREATE TABLE IF NOT EXISTS sms_settings_tbl (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    gateway_url VARCHAR(255),
    api_key VARCHAR(255),
    sender_id VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
"""
cursor.execute(sql)
db.commit()

# Also insert a default row if it doesn't exist
cursor.execute("SELECT COUNT(*) FROM sms_settings_tbl")
if cursor.fetchone()[0] == 0:
    cursor.execute("INSERT INTO sms_settings_tbl (gateway_url, api_key, sender_id, is_active) VALUES ('', '', '', 1)")
    db.commit()

print('Table sms_settings_tbl created and initialized successfully')
