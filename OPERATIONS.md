# 📋 Ruposhi POS — Operations Guide
> এই ফাইলটি সব কিছুর reference। কিছু ভুলে গেলে এখানে দেখুন।
> **Git-এ backup আছে** — কখনো হারাবে না।

---

## 🔐 1. Password Reset (পাসওয়ার্ড ভুলে গেলে)

### Server (SSH) থেকে:
```bash
# সব user দেখুন
php artisan pos:reset-password --list

# নির্দিষ্ট role দেখুন
php artisan pos:reset-password --list --role=admin
php artisan pos:reset-password --list --role=super_admin

# পাসওয়ার্ড রিসেট করুন
php artisan pos:reset-password email@example.com newpassword123

# Random password দিতে চাইলে (screen-এ দেখাবে)
php artisan pos:reset-password email@example.com
```

### App থেকে (UI):
- **Super Admin** → নিজের shop-এর admin/staff-এর password: `মালিক প্যানেল → শাখা → পাসওয়ার্ড রিসেট`
- **Root** → যেকোনো client-এর password: `Root Panel → ক্লায়েন্ট → দেখুন`

---

## 🚀 2. Production Deploy (নতুন update server-এ দেওয়া)

### Step 1 — Local-এ code commit করুন:
```bash
git add -A
git commit -m "your message"
git push origin main
```

### Step 2 — Server-এ SSH করুন, তারপর:
```bash
cd ~/domains/pos.numaanhussain.com/pos_app

# Code pull করুন
git pull origin main

# Database migration (নতুন table থাকলে)
php artisan migrate

# Cache clear করুন (সবসময় করুন)
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

### ⚠️ নিয়ম:
- `git pull` — সবসময় `origin main` দিয়ে (শুধু `git pull` কাজ করে না)
- Cache clear — প্রতিবার deploy-এর পরে করতেই হবে
- Migration — নতুন feature-এ নতুন table আসে, তখন দরকার

---

## 💾 3. Database Backup (ডেটা backup)

### Production থেকে backup নিন:
```bash
# SSH করার পরে:
cd ~/domains/pos.numaanhussain.com/pos_app

# Backup file তৈরি করুন (তারিখ সহ)
mysqldump -u [db_user] -p [db_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# বড় file compress করতে:
mysqldump -u [db_user] -p [db_name] | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Local থেকে backup নিন (Laragon):
```bash
# Windows CMD / PowerShell:
mysqldump -u root new_pos > C:\backup\new_pos_backup.sql
```

### Hostinger control panel থেকেও backup নেওয়া যায়:
`hPanel → Databases → phpMyAdmin → Export → Quick → Go`

---

## 👤 4. নতুন Client যোগ করুন (New Super Admin)

```
Root Panel → ক্লায়েন্ট → নতুন ক্লায়েন্ট
```
- নাম, email, password দিন
- Plan ও max_shops সেট করুন
- লাইসেন্স দিন (expire date সহ)

---

## 🔑 5. লাইসেন্স নবায়ন (License Renew)

```
Root Panel → ক্লায়েন্ট → [client নাম] → লাইসেন্স নবায়ন
```
অথবা Reseller থেকে:
```
Reseller Panel → আমার Client → [client] → লাইসেন্স বাড়ান
```

---

## 🔒 6. Shop Lock / Unlock

### Shop কেন lock হয়?
- লাইসেন্স-এর `max_shops` সীমা পার হলে পুরনো shop lock হয়
- লাইসেন্স expire হলে সব shop lock হয়

### Unlock করতে:
1. লাইসেন্স নবায়ন করুন অথবা `max_shops` বাড়ান
2. Super Admin dashboard-এ গেলে auto-sync হবে

---

## 🛠️ 7. Common Problems (সাধারণ সমস্যা)

| সমস্যা | কারণ | সমাধান |
|--------|------|--------|
| Login হচ্ছে না | পাসওয়ার্ড ভুল | Section 1 দেখুন |
| "শাখাটি লক করা আছে" | License limit / expired | Section 6 দেখুন |
| Page দেখাচ্ছে না (500 error) | Code error বা cache | `php artisan view:clear` চালান |
| নতুন feature কাজ করছে না | Cache পুরনো | Section 2-এর cache clear চালান |
| "Subscription Expired" | License শেষ | Section 5 দেখুন |

---

## 🌿 8. Git — Code Backup

### সব local change save করুন:
```bash
git add -A
git commit -m "কাজের বিবরণ"
```

### GitHub-এ push করুন (backup):
```bash
git push origin main
```

### পুরনো version দেখুন:
```bash
git log --oneline -20
```

### কোনো file কখন কী ছিল দেখুন:
```bash
git log --oneline -- app/Http/Controllers/SaleController.php
```

### ভুল হয়ে গেলে আগের version-এ ফিরুন:
```bash
# শুধু দেখুন, কিছু change হবে না:
git show HEAD~1:app/Http/Controllers/SaleController.php

# ফাইলটা আগের অবস্থায় ফিরিয়ে আনুন:
git checkout HEAD~1 -- app/Http/Controllers/SaleController.php
```

---

## 📊 9. Demo / Test Accounts

| Email | Password | Role | কাজ |
|-------|----------|------|-----|
| `root@system.com` | `password` | root | সব কিছু দেখা ও manage |
| `super@admin.com` | `password` | super_admin | ব্যবসার মালিক panel |
| `admin@inventory.com` | `password` | admin | প্রধান শাখা |
| `mirpur@shop.com` | `password` | admin | মিরপুর শাখা |
| `hasan@inventory.com` | `password` | staff | Staff account |
| `resell@a.com` | `password` | reseller | Reseller panel |

> ⚠️ Production-এ এই accounts-এর password অবশ্যই বদলে নিন।

---

## 🖥️ 10. Server Info

| বিষয় | তথ্য |
|------|------|
| Production URL | `pos.numaanhussain.com` |
| Server | Hostinger shared hosting |
| App path | `~/domains/pos.numaanhussain.com/pos_app` |
| Public path | `~/domains/pos.numaanhussain.com/public_html` (symlink) |
| GitHub | `https://github.com/tanvirs2/ruposhi.git` |
| Branch | `main` |
| PHP | 8.2 |
| DB | MySQL 8 |

---

## ⚡ 11. Quick Commands (দ্রুত reference)

```bash
# Server-এ deploy (এই একটাই মনে রাখুন)
cd ~/domains/pos.numaanhussain.com/pos_app && git pull origin main && php artisan migrate && php artisan config:clear && php artisan route:clear && php artisan view:clear

# User list দেখুন
php artisan pos:reset-password --list

# Password reset
php artisan pos:reset-password email@example.com newpassword

# Cache clear
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Log দেখুন (last 50 lines)
tail -50 storage/logs/laravel.log
```

---

## 📞 12. কিছু না বুঝলে

1. এই ফাইলটা আবার পড়ুন
2. `CLAUDE.md` — technical details এখানে আছে
3. Error message দেখুন: `storage/logs/laravel.log`
4. Claude-এ জিজ্ঞেস করুন — project context সে জানে

---

*Last updated: 2026-06-05 | Ruposhi POS v2 Multi-Shop*
