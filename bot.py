from telegram import Update
from telegram.ext import ApplicationBuilder, CommandHandler, MessageHandler, filters, ContextTypes
import os

TOKEN = "8583621506:AAE6DR9bNJ4OYEbEK08bEMFxxO4-PdzJKcA"

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    await update.message.reply_text("Send me your .java file to obfuscate.")

async def handle_file(update: Update, context: ContextTypes.DEFAULT_TYPE):
    file = update.message.document
    if not file.file_name.endswith(".java"):
        await update.message.reply_text("Please send a .java file only.")
        return

    new_file = await file.get_file()
    await new_file.download_to_drive(file.file_name)

    await update.message.reply_text("File received. Processing...")

    os.system(f"python obfuscator.py {file.file_name}")

    obf_file = "obf_" + file.file_name
    await update.message.reply_document(open(obf_file, "rb"))

app = ApplicationBuilder().token(TOKEN).build()
app.add_handler(CommandHandler("start", start))
app.add_handler(MessageHandler(filters.Document.ALL, handle_file))

app.run_polling()