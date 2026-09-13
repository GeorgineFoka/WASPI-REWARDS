# WASPI REWARDS

A reward management system for points, badges, and activity feeds, developed as part of the **Waspito Full Stack Technical Test**.

* **GitHub Repository:** https://github.com/GeorgineFoka/WASPI-REWARDS
* **Live Demo:** https://waspi-rewards-production.up.railway.app

---

## 🚀 Tech Stack

* **Back-end:** PHP 8+ & Laravel
* **Front-end:** Vue.js 3 (Composition API), Tailwind CSS & SCSS
* **Database:** PostgreSQL / MySQL
* **API:** RESTful API
* **Authentication:** Access-token based API authentication

---

## ✨ Features & Business Rules

The application rewards users based on their interactions, such as comments and likes. Points and badges are assigned according to predefined milestones.

### 🏆 Badge System

| Badge              | Requirement         |    Points |
| ------------------ | ------------------- | --------: |
| **Beginner Badge** | First comment       |    50 pts |
| **Beginner**       | 10 likes or more    |   500 pts |
| **Top Fan**        | 30 comments or more | 2,500 pts |
| **Super Fan**      | 50 comments or more | 5,000 pts |

### Implemented Features

* **User Management:** Display of users, points, current badge, and dynamic calculation of the next badge milestone through `next_badge_info`.
* **Comments:** Add comments through an interactive modal.
* **Comment Deletion:** Delete comments as an additional feature.
* **Likes:** Like and unlike comments dynamically as an additional feature.
* **Advanced Filters:** Filter users by badge type and minimum points threshold.
* **Debounced Filtering:** Prevent unnecessary API requests while filtering.
* **Responsive UI:** Mobile-friendly card layout and desktop table layout.
* **API:** REST API for retrieving user reward information.

---

## 🔌 API Endpoints

The API requires a valid access token.

### Get Users

**`GET /api/users`**

#### Query Parameters

| Parameter      | Required | Description                                      |
| -------------- | -------- | ------------------------------------------------ |
| `access_token` | Yes      | API access token                                 |
| `type`         | No       | Filter by badge type, e.g. `beginner`, `top-fan` |
| `points`       | No       | Filter by minimum points                         |

#### Example

```http
GET /api/users?access_token=waspi_secret_token_2026&type=top-fan&points=500
```

#### Response

Returns a JSON response containing the list of users, their points, current badge, and information about their next badge milestone.

---

## 🛠️ Installation and Setup

### 1. Clone the Repository

```bash
git clone https://github.com/GeorgineFoka/WASPI-REWARDS.git
cd WASPI-REWARDS
```

### 2. Install Back-end Dependencies

```bash
composer install
```

### 3. Configure Laravel

Create your environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database credentials in `.env`, then run:

```bash
php artisan migrate --seed
```

### 4. Start the Laravel Development Server

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

### 5. Install Front-end Dependencies

From the front-end directory:

```bash
npm install
```

### 6. Start the Front-end Development Server

```bash
npm run dev
```

---

## 🧪 Tests

Run the automated test suite to validate the application's business rules, badge assignments, point calculations, and API endpoints:

```bash
php artisan test
```

---

## 📌 Project Structure

The project is organized into separate back-end and front-end components:

* **Laravel:** API, business logic, models, database, and automated tests.
* **Vue.js:** User interface and interactive features.
* **Tailwind CSS / SCSS:** Responsive and customized styling.

---

## 🚀 Live Demo

The deployed application is available here:

https://waspi-rewards-production.up.railway.app

The project can also be reviewed through the GitHub repository:

https://github.com/GeorgineFoka/WASPI-REWARDS
