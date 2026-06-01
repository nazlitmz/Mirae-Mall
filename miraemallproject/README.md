🛍️ Mirae Mall
미래 — Future
A full-stack digital mall platform built with PHP, MySQL & JavaScript
🌏 Why "Mirae"?
Mirae (미래) is the Korean word for "future".
The name wasn't chosen randomly. I wanted this project to feel forward-looking not like a typical university assignment, but like something that could actually exist. A digital mall should feel modern, clean, and purposeful. That's what Mirae is.

✨ Features

👤 Customer Portal

Test Account: nazlitemiz45@gmail.com // Manulas4

Multi-step signup with real-time password strength meter
Browse shops with category filter (Clothing, Electronics, Coffeeshop, Restaurant)
Product carousel with Bootstrap image slider
Add to cart & confirm orders with tax calculation (18% VAT)
Order history with live status tracking
Rate shops after delivery (⭐ star rating)
Dark / Light mode toggle — preference saved in localStorage

🏪 Manager Portal

Test Account:
m.ozgurtemiz@gmail.com // Manulas4
esraozyakatemiz@gmail.com // Manulas4
cansuozyaka@gmail.com// Manulas4
umuttuna03@gmail.com// Manulas4

Dashboard with shop overview and order statistics
Full product management — add, edit, delete with image upload
Order pipeline — view and update order statuses
Separate deliveries view for fulfilled orders

🔐 Admin Portal
Test Account:
admin@mirae.com // password

Separate secure login (/admin/secure_login.php) — not publicly linked
Real-time stats: total revenue, orders, users, shops
Revenue & Orders line chart — real monthly data via order_date
Shop type donut chart — live distribution from DB
User management — search, filter by role, edit, add managers
Shop management — status control (active / pending / suspended), delete
Paginated tables with search across all management pages

🗄️ Database Schema

Database: mirae_db
sqlusers → id, name, email, password, address, role
role: admin / manager / customer

shops → id, name, type, location, manager_id, rating, status
status: active / pending / suspended

products → id, name, description, price, quantity, shop_id, rating

orders → id, order_date, status, customer_id, total, tax
status: pending → accepted → being_prepared → shipped → delivered

order_line → id, product_id, order_id, amount, total_price

cart → id, order_id, order_line_id, customer_id, payment_method

pictures → id, product_id, path
supports multiple images per product
