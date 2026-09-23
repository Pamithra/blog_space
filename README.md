# BlogSpace – Full-Stack Blogging & Publishing Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Render](https://img.shields.io/badge/Deployed_on-Render-46E3B7?logo=render&logoColor=white)](https://blog-space-b5ef.onrender.com)
[![Docker](https://img.shields.io/badge/Container-Docker-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A secure, responsive, and modern full-stack blogging platform built with **PHP 8**, **MySQL**, and vanilla **JavaScript (ES6)**. Designed and engineered for the **University of Moratuwa (IN2120 – Web Programming)** with production-ready cloud deployment on **Render**.

🌐 **Live Website**: [https://blog-space-b5ef.onrender.com](https://blog-space-b5ef.onrender.com)  
📁 **GitHub Repository**: [https://github.com/Pamithra/blog_space](https://github.com/Pamithra/blog_space)

---

## 🌟 Key Features

### 1. User Authentication & Authorization (RBAC)
* **Secure Registration & Login**: Password hashing using Bcrypt (`PASSWORD_DEFAULT`), session fixation protection (`session_regenerate_id()`), and `HttpOnly` cookie flags.
* **Show/Hide Password**: Interactive eye toggle button for password visibility on both login and registration forms.
* **Role-Based Authorization**: Post authors can only edit or delete their own posts; administrators have platform-wide moderation rights.

### 2. Rich Content Management & Markdown Editor
* **Interactive Markdown Editor**: Integrated with **EasyMDE** featuring live split-screen preview, styling toolbar (headings, bold, lists, quotes, inline code, and code blocks).
* **XSS Sanitization**: Two-pass HTML entity escaping and Markdown parser to eliminate Cross-Site Scripting (XSS) risks while preserving formatted code blocks.
* **Dual-Mode Media Storage**: Seamlessly uploads to **Cloudinary** for persistent media on Render/cloud, with an automatic fallback to local `uploads/` for local XAMPP environments.

### 3. Modern Interactive UI / UX (2026 Standards)
* **Dark / Light Theme Switcher**: Modern theme toggle icon with user preference saved in `localStorage`.
* **Responsive Mobile Navigation**: Glassmorphic sticky navbar with a responsive slide-out hamburger drawer menu.
* **Asynchronous Engagement**: Instant like/unlike system with live counter, comment threads, reading time estimator ("3 min read"), post view tracking, and one-click social sharing (X / Twitter, LinkedIn, Copy Link).
* **Toast Notification Engine**: Floating toast notifications for likes, comments, newsletter signups, and authentication notices.
* **Tag & Category Browsing**: Interactive Category pill filters and dynamic hashtag filtering on the homepage.
* **Working Pagination**: Server-side pagination controls across blog listings.

### 4. Author Profiles & Admin Dashboard
* **Public Author Pages**: Dedicated profile page (`author.php?id=X`) displaying author bio, join date, total article count, total likes received, and published articles.
* **Admin Analytics Dashboard**: High-level platform metrics (Users, Articles, Total Views, Likes, Comments, Newsletter Subscribers) with content management tables.

---

## 🛠️ Tech Stack

* **Backend**: PHP 8.2+ (Procedural + OOP PDO MySQL)
* **Database**: MySQL 8.0+ / TiDB Cloud Serverless (InnoDB, UTF8mb4, Normalized Relational Schema)
* **Frontend**: Vanilla JavaScript (ES6 Fetch API), Semantic HTML5, Modern CSS Custom Properties
* **Deployment & Containerization**: Docker (Apache + PHP 8.2), Render Web Service, Cloudinary (Media)

---

## 📁 Project Structure

```text
├── admin/
│   └── index.php              # Administrator analytics dashboard
├── assets/
│   ├── css/
│   │   └── styles.css         # Modern responsive CSS (Dark/Light variables)
│   ├── js/
│   │   └── scripts.js         # Interactive client-side scripts & toast engine
│   └── default-avatar.svg     # Vector fallback profile avatar
├── auth/
│   ├── login.php              # User authentication with eye toggle
│   ├── logout.php             # Session destruction & sign out
│   └── register.php           # Account registration with validation
├── inc/
│   ├── cacert.pem             # Mozilla trusted root CA bundle for cloud SSL
│   ├── config.example.php     # Template configuration file
│   ├── config.php             # Dynamic environment & session configuration
│   ├── db.php                 # PDO database layer with SSL auto-negotiation
│   ├── footer.php             # Clean layout footer & script imports
│   ├── header.php             # SEO metadata, navbar & theme toggle
│   └── helpers.php            # Security, Markdown parser, image handlers
├── posts/
│   ├── comment_add.php        # Comment processing endpoint
│   ├── delete.php             # Post deletion with transaction
│   ├── edit.php               # EasyMDE post editor with preloaded tags
│   ├── new.php                # EasyMDE post creator
│   └── view.php               # Single post view with view tracking & XSS protection
├── uploads/                   # Local media storage directory
├── author.php                 # Public author profile & published articles
├── index.php                  # Homepage with search, filter & pagination
├── init.sql                   # Database initialization & migrations
├── Dockerfile                 # Render / Docker production container
├── .htaccess                  # Apache rewrite & security headers
└── README.md                  # Project documentation & setup guide
```

---

## 🚀 Running Locally (XAMPP / WAMP)

1. **Clone the repository** into your local web root:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Pamithra/blog_space.git blog_space
   ```

2. **Start Apache and MySQL** via the XAMPP Control Panel.

3. **Import the Database**:
   * Open `http://localhost/phpmyadmin/` in your browser.
   * Create a new database named `blog_db` with `utf8mb4_unicode_ci` collation.
   * Go to the **Import** tab and select `init.sql` from the project root.
   * Click **Go / Import**.

4. **Access the Website**:
   * Visit: `http://localhost/blog_space/`
   * Default Administrator Account:
     * **Username**: `admin`
     * **Password**: `Password123`

*(Note: `inc/config.php` automatically detects `localhost` and connects to XAMPP default settings with zero configuration required).*

---

## ☁️ Cloud Deployment on Render (with TiDB Cloud)

1. **Database (TiDB Cloud Serverless)**:
   * Create a free Serverless cluster on [TiDB Cloud](https://tidbcloud.com/).
   * In the SQL Editor, execute `init.sql` to generate all tables and default data.
2. **Deploy on Render**:
   * Link your GitHub repository to a new **Render Web Service**.
   * Runtime: **Docker** (automatically detected from `Dockerfile`).
   * Instance Type: **Free** ($0/month).
   * Add the following **Environment Variables**:
     * `DB_HOST`: Your TiDB cluster host
     * `DB_PORT`: `4000`
     * `DB_NAME`: `test` (or `blog_db`)
     * `DB_USER`: Your TiDB username
     * `DB_PASS`: Your TiDB password
     * `SITE_NAME`: `BlogSpace`
   * Click **Deploy web service**.

---

## 🔒 Security Summary

* **SQL Injection**: Neutralized via PDO prepared statements with strict parameter binding on all database queries.
* **Cross-Site Scripting (XSS)**: Neutralized through pre-sanitization of Markdown inputs and HTML entity escaping.
* **Cross-Site Request Forgery (CSRF)**: State-changing operations verify cryptographic session tokens generated with `random_bytes(16)`.
* **Password Storage**: Passwords hashed with one-way cryptographic salt using Bcrypt (`PASSWORD_DEFAULT`).
* **Session Protection**: Hardened cookies with `HttpOnly` and `SameSite` flags, plus `session_regenerate_id(true)` to eliminate session fixation.

---

## 👤 Author

**Pamithra Jayawardena**  
B.Sc. (Hons) in Information Technology & Management (Undergraduate)  
Faculty of Information Technology, University of Moratuwa  
* GitHub: [@Pamithra](https://github.com/Pamithra)  
* Email: pamithrajithmini2004@gmail.com
