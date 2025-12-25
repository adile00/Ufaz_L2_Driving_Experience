# Ufaz_L2_Driving_Experience
Backend Project 

🚗 Driving Experience Tracker – Back-End Web Application

📌 Project Overview

This project is a Back-End web application developed as part of the UFAZ Computer Science (L2) PHP & MySQL coursework.
It allows users to record, analyze, and visualize supervised driving experiences, extending a previously designed Front-End and Database project.

The application focuses on data integrity, security, usability, and responsive design, following the technical requirements provided by the instructor.


🎯 Main Features
✅ Driving Experience Management

Add a new driving experience with:

Date

Start time & end time (validated)

Distance in kilometers

Weather conditions

Speed limit

Traffic density

Visibility conditions

Maneuvers performed (many-to-many relationship)

Edit existing driving experiences

Delete driving experiences



📊 Dashboard & Analytics

Summary statistics:

Total number of drives

Total kilometers

Total driving hours

Interactive charts using Chart.js:

Weather distribution

Traffic density distribution

Kilometers evolution over time

Most performed maneuvers

Filter driving experiences by:

Date range

Weather

Speed limit

Traffic density

Visibility

Maneuvers



📱 Responsive Design

Fully mobile-friendly

Desktop:

DataTables for sorting & pagination

Mobile:

Card-based layout for better readability

Custom CSS using Grid and Flexbox



🧩 Technical Stack
Back-End

PHP 8

PDO (PHP Data Objects) with prepared statements

Secure database transactions

Object-Oriented Programming (OOP)

Database

MySQL

Normalized schema

Many-to-many relationship for maneuvers

JOIN queries for summaries and analytics

Front-End

HTML5 semantic elements

CSS Grid & Flexbox

Handwritten responsive CSS

JavaScript (vanilla + jQuery)

Chart.js

DataTables

Security

PHP sessions

CSRF protection

Server-side validation

Prepared SQL queries

ID anonymization using tokens

🗄️ Database Structure (Simplified)

driving_experiences

weather_conditions

speed_limits

traffic_densities

visibility_conditions

maneuvers

experience_maneuver (many-to-many)

users



🔐 Security Measures

CSRF tokens for all POST requests

Session-based token verification

Server-side validation for:

Dates

Time consistency

Numeric inputs

Prepared SQL statements to prevent SQL injection



🌐 Deployment

Hosted on AlwaysData

Public URL provided for evaluation

Database credentials secured

Password masked for repository submission



📂 Repository Structure
/
├── form.php
├── insert.php
├── dashboard.php
├── edit.php
├── delete.php
├── init.php
├── csrf.php
├── id_token.php
├── DrivingExperience.php
├── success.php
├── nav.php
├── assets/
│   └── background1.avif
├── README.md



🧪 Evaluation Criteria Coverage

✔ PDO with prepared statements
✔ OOP implementation
✔ Many-to-many relationship
✔ Responsive web form
✔ Secure data handling
✔ Graphical analytics
✔ Mobile & desktop views
✔ Filtering & sorting
✔ Remote hosting


✍️ Author

Adelya
Computer Science Student – UFAZ
Course: PHP & MySQL Back-End Development
