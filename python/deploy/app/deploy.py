import subprocess
import os
import logging
import config
from fastapi import HTTPException

logger = logging.getLogger(__name__)

GITHUB_USER = os.getenv('GITHUB_USER')

if not GITHUB_USER:
    logger.error("❌ No github user")
    raise ValueError("GITHUB_USER is not set in .env")

def run_command(command, cwd):

    result = subprocess.run(command, shell=True, cwd=cwd, capture_output=True, text=True)

    if result.returncode != 0:

        logger.error(f"❌ Command failed: {command}")

        if result.stderr:
            logger.error(f"stderr: {result.stderr}")
        if result.stdout:
            logger.error(f"stdout: {result.stdout}")
            
        return False

    return True

def deploy(project_name):

    if project_name not in config.PROJECTS:
        logger.error(f"❌ Project '{project_name}' not found")
        raise HTTPException(status_code=404, detail=f"Project '{project_name}' not found")

    path = os.path.join(config.PROJECTS_PATH, project_name)
    branch = config.PROJECTS[project_name]['branch']

    logger.info(f"🚀 Starting deployment for {path}")

    if not os.path.exists(path):
        logger.error(f"❌ Path {path} does not exist.")
        raise HTTPException(status_code=404, detail=f"❌ Path {path} does not exist.")

    if not os.path.exists(os.path.join(path, '.git')):
        # Клонируем репозиторий
        ssh_url = f"git@github.com:{GITHUB_USER}/{project_name}.git"
        logger.info(f"Cloning {ssh_url} into {path}")
        run_command(f'git clone --depth 1 {ssh_url} {path}', config.PROJECTS_PATH)
    else:
        # Обновляем репозиторий
        if not run_command(f'git fetch --depth 1 origin {branch}', path): return
        if not run_command(f'git reset --hard origin/{branch}', path): return

    logger.info("✅ Deployment finished successfully!")
