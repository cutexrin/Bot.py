from telegram import Update
from telegram.ext import ApplicationBuilder, CommandHandler, MessageHandler, filters, ContextTypes
import os

TOKEN = os.getenv("8583621506:AAE6DR9bNJ4OYEbEK08bEMFxxO4-PdzJKcA")

UPLOAD_DIR = "uploads"
os.makedirs(UPLOAD_DIR, exist_ok=True)

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    await update.message.reply_text("Send me your APK file. I will protect it 🔒")

async def handle_file(update: Update, context: ContextTypes.DEFAULT_TYPE):
    file = update.message.document

    if not file.file_name.endswith(".apk"):
        await update.message.reply_text("❌ Only APK files allowed.")
        return

    await update.message.reply_text("📥 APK received. Saving...")

    new_file = await file.get_file()
    save_path = os.path.join(UPLOAD_DIR, file.file_name)
    await new_file.download_to_drive(save_path)

    await update.message.reply_text(
        f"✅ Saved: {file.file_name}\nNext: Decompile → Smali → Obfuscate"
    )

app = ApplicationBuilder().token(TOKEN).build()
app.add_handler(CommandHandler("start", start))
app.add_handler(MessageHandler(filters.Document.ALL, handle_file))

app.run_polling()
