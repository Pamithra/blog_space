# BlogSpace – Web Blog Application

A clean, modern, and easy-to-use blogging website built with **PHP**, **MySQL**, and **JavaScript**. 

Users can register, write articles using a formatting editor with live preview, like posts, leave comments, and switch between dark and light themes.

---

## ✨ Features

### 1. User Accounts & Security
* **Register & Log In**: Create an account with a username, email, and password.
* **Show / Hide Password**: Click the eye icon inside the password box to easily see what you typed.
* **Safe & Protected**: Passwords are encrypted before saving, and forms are protected so unauthorized users cannot edit or delete someone else's posts.
* **Admin Role**: An administrator can view all users, manage articles, and see website statistics.

### 2. Writing & Reading Blogs
* **Easy Blog Editor**: Write articles with headings, bold text, lists, and code blocks using a built-in Markdown editor with a **live preview**.
* **Cover Images**: Add a cover picture to any article.
* **Reading Time & Views**: Automatically shows how long an article takes to read (e.g. *3 min read*) and tracks how many times people opened it.
* **Search & Filter**: Search for articles by keyword, or click on any category pill or hashtag to see related stories.
* **Author Profiles**: Click on any writer's name to view their profile, bio, and all articles they have published.

### 3. Modern Design & Interaction
* **Dark & Light Mode**: Switch between dark mode and light mode with one click. The website remembers your choice.
* **Instant Likes & Comments**: Click the heart button or add a comment, and the page updates right away without reloading.
* **Copy Link Button**: Share any article with a single click. A small popup confirms the link was copied.
* **Mobile Friendly**: Works smoothly on mobile phones, tablets, and desktop computers with a slide-out menu.

---

## 🛠️ Built With

* **Frontend**: HTML5, CSS3, JavaScript (Fetch API)
* **Backend**: PHP 8
* **Database**: MySQL (PDO)
* **Cloud Hosting**: Render (using Docker) and TiDB Cloud

---

## 💻 How to Run Locally on Your Computer (with XAMPP)

Follow these 4 simple steps to run this project on your laptop:

### Step 1: Download or Clone
Copy or clone this project folder into your XAMPP `htdocs` folder:
```bash
C:\xampp\htdocs\blog_space
```

### Step 2: Start XAMPP
Open the **XAMPP Control Panel** and click **Start** for both **Apache** and **MySQL**.

### Step 3: Set Up Database
1. Open your browser and go to: `http://localhost/phpmyadmin/`
2. Click **New** on the left side to create a database.
3. Name it: **`blog_db`** and click **Create**.
4. Click on **`blog_db`**, then click the **Import** tab at the top.
5. Click **Choose File**, select the **`init.sql`** file from this project folder, and click **Import** (or **Go**).

### Step 4: Open the Website
In your web browser, visit:  
👉 **`http://localhost/blog_space/`**

---

## 📁 Project Structure

```text
Blog space/
├── admin/
│   └── index.php              # Admin dashboard & analytics
├── assets/
│   ├── css/
│   │   └── styles.css         # Styling (Dark & Light themes)
│   ├── js/
│   │   └── scripts.js         # Interactive scripts, likes & toasts
│   └── default-avatar.svg     # Default profile picture
├── auth/
│   ├── login.php              # Login page with password eye toggle
│   ├── logout.php             # Logout script
│   └── register.php           # User registration
├── inc/
│   ├── cacert.pem             # SSL certificate for cloud database
│   ├── config.example.php     # Example configuration file
│   ├── config.php             # Main website configuration
│   ├── db.php                 # Database connection layer
│   ├── footer.php             # Page footer & newsletter form
│   ├── header.php             # Navigation bar & theme switch
│   └── helpers.php            # Security, Markdown & helper functions
├── posts/
│   ├── comment_add.php        # Comment submission handler
│   ├── delete.php             # Delete blog post
│   ├── edit.php               # Edit blog post (Markdown editor)
│   ├── new.php                # Write new post (Markdown editor)
│   └── view.php               # Single article view & reading time
├── uploads/                   # Uploaded blog images
├── author.php                 # Author profile & their articles
├── index.php                  # Homepage with search & categories
├── init.sql                   # Database tables and sample data
├── Dockerfile                 # Cloud container configuration
├── .htaccess                  # Server security rules
└── README.md                  # Project documentation
```

---


