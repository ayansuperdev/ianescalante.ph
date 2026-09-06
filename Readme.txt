Features of This Implementation:
Modern UI Design:

Clean, responsive layout with attractive colors and shadows

Mobile-friendly design

Smooth transitions and hover effects

Security:

Password hashing with PHP's password_hash()

Prepared statements to prevent SQL injection

Session management

Role-based access control

Functionality:

Separate dashboards for admin and regular users

Admin can view all users

User management interface

Clean logout functionality

Database:

MySQL database with users table

Proper table structure with roles

Code Organization:

Separated concerns (config, auth, presentation)

Reusable functions

Proper error handling

To use this system:

Create the database and tables as shown

Place all files in your web server directory

Adjust database credentials in config.php

Access the login page at index.php

Default credentials:

Admin: username "admin", password "Admin123!"

User: username "user1", password "User123!"

/PORTFOLIO/
│── assets/
│   │── css/
│   │   │── style.css  # Main stylesheet
│   │   └── dark.css
│   │── images/
│   │   └── default-avatar.png 
│   └── js/
│       │──datatables-loader.js
│       │── plugins.js
│       │──plugin-safety-wrapper.js
│       └──plugin-wrapper.js
│── includes/
│   │── admin/
│   │   │── add_user.php
│   │   │── messages.php
│   │   │── plugins.php
│   │   │── profile.php
│   │   │── settings.php
│   │   │── upload_plugin.php
│   │   └── users.php
│   │── user/
│   │   │── messages.php
│   │   │── profile.php
│   │   └── settings.php
│   │── active_plugins.php
│   │── auth.php       # Authentication functions
│   │── config.php     # Database configuration
│   │── helpers.php 
│   │── load_plugins.php
│   └── plugin_api.php
│   └── plugin_loader.php
│── plugins/           # Route for uploaded plugin
│── screenshot/
│   └── Screenshot 2025-05-29 213410.png
│── temp/
│── uploads/
│   └── avatars/
│       │── avatar_3_6835ecf9b6011.jpg
│       └── avatar_4_6835e6b5cce45.png
│── wp-admin/
│   └── includes/
│       └── upgrade.php
│── admin_dashboard.php # Admin dashboard
│── change_password.php 
│── dashboard.php      # Dashboard router
│── index.php          # Login page
│── logout.php         # Logout script
│── mark_read.php
│── Readme.txt
│── reset_passwords.php
│── send_message.php
│── test_upload.php
└── user_dashboard.php  # User dashboard
