import hmac
import hashlib
import logging
import os
from deploy import deploy
from fastapi import FastAPI, Request, HTTPException, BackgroundTasks

logging.basicConfig(
    level=logging.DEBUG,
    filename="/app/logs/main.log",
    format='%(asctime)s %(levelname)s: %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

logger = logging.getLogger(__name__)

SECRET_TOKEN = os.getenv('GITHUB_WEBHOOK_SECRET')

if not SECRET_TOKEN:
    logger.error("❌ No secret token")
    raise ValueError("GITHUB_WEBHOOK_SECRET is not set in .env")

app = FastAPI()

def verify_signature(payload_body: bytes, signature_header: str) -> bool:

    if not signature_header:
        logger.error("❌ No signature in header")
        return False
    
    hash_object = hmac.new(SECRET_TOKEN.encode('utf-8'), msg=payload_body, digestmod=hashlib.sha256)
    expected_signature = 'sha256=' + hash_object.hexdigest()

    return hmac.compare_digest(expected_signature, signature_header)

@app.post("/deploy/{project_name}")
async def github_webhook(project_name: str, request: Request, background_tasks: BackgroundTasks):

    body = await request.body()
    signature = request.headers.get('X-Hub-Signature-256')

    if not verify_signature(body, signature):
        logger.error("❌ Invalid signature")
        raise HTTPException(status_code=401, detail="Invalid signature")

    if request.headers.get('X-GitHub-Event') != 'push':
        logger.error("❌ No push event")
        return {"status": "ignored"}
    
    background_tasks.add_task(deploy, project_name)

    return {"status": "deploy started"}

@app.get("/health")
async def health():
    return {"status": "ok"}