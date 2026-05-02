import os
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from dotenv import load_dotenv

load_dotenv()

MAIL_HOST = os.getenv('MAIL_HOST', 'smtp.gmail.com')
MAIL_PORT = int(os.getenv('MAIL_PORT', '587'))
MAIL_USER = os.getenv('MAIL_USERNAME', '')
MAIL_PASS = os.getenv('MAIL_PASSWORD', '')

def test_mail():
    print(f"Attempting to send test mail from {MAIL_USER}...")
    try:
        msg = MIMEMultipart()
        msg['Subject'] = "CircuLens Email Test"
        msg['From'] = MAIL_USER
        msg['To'] = MAIL_USER # Send to self

        body = "Hello! Your CircuLens email system is now configured correctly."
        msg.attach(MIMEText(body, 'plain'))

        server = smtplib.SMTP(MAIL_HOST, MAIL_PORT)
        server.starttls()
        server.login(MAIL_USER, MAIL_PASS)
        server.sendmail(MAIL_USER, MAIL_USER, msg.as_string())
        server.quit()
        print("Success! Test email sent.")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    test_mail()
