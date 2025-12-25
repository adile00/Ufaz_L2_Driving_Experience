# Ufaz_L2_Driving_Experience
Backend Project 


🚗 Supervised Driving Experience — Back-End Web Application

This project is a Back-End web application (PHP & MySQL) developed as part of the UFAZ Web Programming homework.
It allows users to record, manage, and analyze supervised driving experiences, building on previous Front-End and Database projects.

The application focuses on data integrity, usability, responsive design, and secure database interactions, without using any PHP framework.

🎯 Project Objectives

Record a driving experience with:

Date, start time, end time

Distance (kilometers)

Weather conditions

Speed limits

Traffic density

Visibility conditions

Multiple maneuvers (many-to-many relationship)

Display a summary dashboard with:

Total drives, kilometers, and hours

Filterable driving experiences

Graphical statistics (charts)

Ensure the application is:

Mobile-friendly

Secure

Easy to use and readable

🧩 Features
✅ Driving Experience Form

Responsive and mobile-friendly form

Default date set to today

Numeric keypad for distance input

Client-side validation:

End time must be later than start time

Maneuvers selection using a custom checkbox UI

All select lists populated dynamically from the database

📊 Dashboard

Summary statistics:

Total drives

Total kilometers

Total driving hours

Advanced filters:

Date range

Weather

Speed limit

Traffic density

Visibility

Maneuvers

Desktop table with DataTables

Sorting enabled only for date, start time, end time, and kilometers

Mobile view with card-based layout

Interactive charts using Chart.js:

Weather distribution

Traffic density

Kilometers evolution by date

Most performed maneuvers

🔐 Security & Data Integrity

Secure database access using PDO

Prepared statements (prepare, bindValue)

CSRF protection for delete actions

Session usage for ID anonymization (tokens instead of raw IDs)

Sensitive configuration files excluded from GitHub

🗄️ Database Design

Relational MySQL database

Normalized structure with:

driving_experiences

weather_conditions

speed_limits

traffic_densities

visibility_conditions

maneuvers

experience_maneuver (many-to-many relationship)

Queries use JOINs to retrieve readable labels (not IDs)

🛠️ Technologies Used

PHP (PDO)

MySQL

HTML5 (semantic markup)

CSS3 (Grid & Flexbox)

JavaScript

jQuery

DataTables

Chart.js

No PHP frameworks (Laravel, Symfony, etc.) were used.

📱 Responsive Design

Optimized for:

Desktop

Tablet

Mobile

Mobile-specific layouts:

Card view for driving experiences

Touch-friendly inputs and buttons

Tested using browser inspect tools

🚀 Deployment

Hosted on AlwaysData

Publicly accessible URL (no authentication required)

GitHub repository contains:

Full source code

README

.gitignore

Example configuration file

📁 Repository Structure
/
├── dashboard.php
├── form.php
├── insert.php
├── edit.php
├── delete.php
├── nav.php
├── DrivingExperience.php
├── csrf.php
├── id_token.php
├── init.example.php
├── assets/
│   └── background images
├── README.md
└── .gitignore

📝 Notes for Evaluation

The project strictly follows the teacher’s technical requirements

Emphasis on:

Clean code

Secure queries

Database integrity

UX & ergonomics

Designed to be clear and easy to evaluate by peers

👩‍💻 Author

Adelya
Computer Science student — UFAZ
Backend development & cybersecurity oriented
