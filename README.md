# BlogSpace – Full-Stack Blogging & Publishing Platform

A secure, responsive, and modern blogging platform built with **PHP 8**, **MySQL**, and vanilla **JavaScript (ES6)**. Designed and engineered to fulfill all academic requirements for the **University of Moratuwa (IN2120 – Web Programming)** with production-ready cloud deployment support on **Render**.

---

## 🌟 Key Features

### 1. User Authentication & Authorization (RBAC)
* **Secure Registration & Login**: Password hashing using Bcrypt (`PASSWORD_DEFAULT`), session fixation protection (`session_regenerate_id()`), and `HttpOnly` cookie flags.
* **Role-Based Authorization**: Post authors can only edit or delete their own posts; administrators have full platform moderation rights.

### 2. Rich Content Management & Markdown Editor
* **Interactive Markdown Editor**: Integrated with **EasyMDE** featuring live split-screen preview, styling toolbar (headings, bold, lists, quotes, inline code, and code blocks).
* **XSS Sanitization**: Two-pass HTML entity escaping and Markdown parser to eliminate Cross-Site Scripting (XSS) risks.
* **Dual-Mode Media Storage**: Seamlessly uploads to **Cloudinary** for persistent media on Render/cloud, with an automatic fallback to local `uploads/` for local XAMPP environments.

### 3. Modern Interactive UI / UX (2026 Standards)
* **Theme Switching**: Dark Mode and Light Mode switcher with user preference persistence via `localStorage`.
* **Mobile Navigation**: Responsive sticky glassmorphism navbar with a slide-out mobile drawer menu.
* **Engagement & Social**: Asynchronous like/unlike system with live counter, comment threads, reading time estimator ("3 min read"), post view tracking, and one-click social sharing (X / Twitter, LinkedIn, Copy Link).
* **Toast Notifications**: Modern floating toast alert system for all user feedback.
* **Tag & Category Browsing**: Dynamic pill filters and search on the homepage.
* **Working Pagination**: Server-side pagination controls across blog listings.

---

## 🛠️ Tech Stack

* **Backend**: PHP 8.2+ (Procedural + OOP PDO MySQL)
* **Database**: MySQL 8.0+ (InnoDB, UTF8mb4, Normalized Relational Schema)
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
│   ├── login.php              # User authentication
│   ├── logout.php             # Session destruction & sign out
│   └── register.php           # Account registration
├── inc/
│   ├── config.example.php     # Template configuration file
│   ├── config.php             # Dynamic environment & session configuration
│   ├── db.php                 # PDO database layer with SSL support
│   ├── footer.php             # Unified layout footer & script imports
│   ├── header.php             # SEO metadata, navbar & theme toggle
│   └── helpers.php            # Security, Markdown parser, image handlers
├── posts/
│   ├── comment_add.php        # Comment processing endpoint
│   ├── delete.php             # Post deletion with transaction
│   ├── edit.php               # EasyMDE post editor
│   ├── new.php                # EasyMDE post creator
│   └── view.php               # Single post view with view tracking
├── uploads/                   # Local media storage directory
├── author.php                 # Public author profile & published articles
├── index.php                  # Homepage with search, filter & pagination
├── init.sql                   # Database initialization & migrations
├── Dockerfile                 # Render / Docker production container
├── .htaccess                  # Apache rewrite & security headers
└── README.md                  # Documentation & deployment guide
```

---

## 🚀 Running Locally (XAMPP / WAMP)

1. **Clone the repository** to your local web root:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Pamithra/blog_space.git blog_space
   ```

2. **Start Apache and MySQL** via the XAMPP Control Panel.

3. **Import the Database**:
   * Open `http://localhost/phpmyadmin/` in your browser.
   * Create a new database named `blog_db` with `utf8mb4_unicode_ci` collation.
   * Click **Import** and select `init.sql` from the project root.
   * Click **Import / Go**.

4. **Access the Application**:
   * Visit: `http://localhost/blog_space/`
   * Default Administrator Account:
     * **Username**: `admin`
     * **Password**: `Password123`

---

## ☁️ Production Deployment on Render

Deploying this PHP & MySQL application to [Render](https://render.com) takes less than 5 minutes using Docker and a free cloud MySQL database.

### Step 1: Set Up a Free Cloud MySQL Database
Render's free tier provides native PostgreSQL, so for MySQL you can use **TiDB Cloud** or **Aiven** (both offer 100% free permanent MySQL databases):
1. Sign up at [TiDB Cloud](https://tidbcloud.com/) (Serverless free tier).
2. Create a free cluster named `blogspace-db`.
3. In the TiDB console, open **Chat2Query** or use the MySQL Web CLI to execute the queries from `init.sql`.
4. Note your database credentials:
   * **Host**: `gateway01....tidbcloud.com`
   * **Port**: `4000`
   * **User**: `your_username.root`
   * **Password**: `your_password`
   * **Database**: `blog_db` (or `test`)

### Step 2: (Optional) Set Up Cloudinary for Media
Render serverless containers have ephemeral filesystems. To ensure uploaded images persist permanently:
1. Create a free account at [Cloudinary](https://cloudinary.com).
2. Copy your **Cloud Name**, **API Key**, and **API Secret** from the Cloudinary dashboard.
*(If you skip this step, image uploads will work locally in `/uploads` on the container).*

### Step 3: Deploy to Render via GitHub
1. Push your repository to **GitHub**:
   ```bash
   git add .
   git commit -m "Full-stack modernization for Render"
   git push origin master
   ```
2. Go to the [Render Dashboard](https://dashboard.render.com/) and click **New +** > **Web Service**.
3. Connect your GitHub repository (`Pamithra/blog_space`).
4. Select **Docker** as the Runtime (Render will automatically detect the `Dockerfile`).
5. Choose the **Free** instance type.
6. Under **Environment Variables**, add the following:

| Variable | Value / Description | Example |
| :--- | :--- | :--- |
| `DB_HOST` | Remote MySQL host | `gateway01.us-east-1.prod.aws.tidbcloud.com` |
| `DB_PORT` | Remote MySQL port | `4000` (or `3306`) |
| `DB_NAME` | Database name | `blog_db` |
| `DB_USER` | MySQL username | `xxxxxx.root` |
| `DB_PASS` | MySQL password | `your_secure_password` |
| `SITE_NAME` | Website name | `BlogSpace` |
| `CLOUDINARY_CLOUD_NAME` | *(Optional)* Cloudinary Cloud Name | `your_cloud_name` |
| `CLOUDINARY_API_KEY` | *(Optional)* Cloudinary API Key | `1234567890` |
| `CLOUDINARY_API_SECRET` | *(Optional)* Cloudinary API Secret | `abcdef123456` |

7. Click **Create Web Service**. Render will build the Docker container and deploy your live site with automatic HTTPS!

---

## 🔒 Security Summary

* **SQL Injection**: Prevented using PDO prepared statements with parameterized inputs on all database queries.
* **Cross-Site Scripting (XSS)**: Neutralized using safe Markdown parsing and strict HTML entity encoding.
* **Cross-Site Request Forgery (CSRF)**: Protected using cryptographically secure tokens (`random_bytes(16)`) and `hash_equals()` verification.
* **Authentication**: Industry-standard Bcrypt password hashing and session fixation resistance.
