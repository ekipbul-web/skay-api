from flask import Flask, request, jsonify
import requests
import random
import time

app = Flask(__name__)
pending_codes = {}
BOT_TOKEN = '8875637835:AAFYihJubBsfNgvGo1p122a_NoXDsvyvU9Y'

@app.route('/')
def home():
    return jsonify({'status': 'SKAY API Running', 'developer': '@TuncaySkay'})

@app.route('/send_code', methods=['POST'])
def send_code():
    data = request.json
    telegram = data.get('telegram', '').replace('@', '')
    username = data.get('username', '')
    if not telegram:
        return jsonify({'success': False, 'message': 'Telegram gerekli'})
    code = str(random.randint(100000, 999999))
    try:
        r = requests.get(f'https://api.telegram.org/bot{BOT_TOKEN}/getUpdates', timeout=5)
        chat_id = None
        if r.status_code == 200:
            for u in reversed(r.json().get('result', [])):
                msg = u.get('message', {})
                if msg.get('from', {}).get('username', '').lower() == telegram.lower():
                    chat_id = msg.get('chat', {}).get('id')
                    break
        if not chat_id:
            return jsonify({'success': False, 'message': f'@{telegram} bota START vermemis!'})
        r2 = requests.post(f'https://api.telegram.org/bot{BOT_TOKEN}/sendMessage', json={'chat_id': chat_id, 'text': f'SKAY API Kod: {code}'}, timeout=5)
        if r2.status_code == 200:
            pending_codes[username] = {'code': code, 'expires': time.time() + 300}
            return jsonify({'success': True, 'message': 'Kod gonderildi!'})
    except: pass
    return jsonify({'success': False, 'message': 'Hata'})

@app.route('/verify_code', methods=['POST'])
def verify_code():
    data = request.json
    u, c = data.get('username', ''), data.get('code', '')
    if u in pending_codes and pending_codes[u]['code'] == c and time.time() < pending_codes[u]['expires']:
        del pending_codes[u]
        return jsonify({'success': True, 'message': 'Dogrulandi!'})
    return jsonify({'success': False, 'message': 'Gecersiz kod!'})
