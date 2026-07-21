# Medical Receptionist System

<p align="center">
  <strong>A modern clinic management platform for patient appointments, records, and AI-powered consultations</strong>
</p>

<p align="center">
  <a href="https://laravel.com" target="_blank"><img src="https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white" alt="Laravel"></a>
  <a href="https://php.net" target="_blank"><img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white" alt="PHP"></a>
  <a href="#license"><img src="https://img.shields.io/badge/License-MIT-green" alt="License"></a>
  <a href="#"><img src="https://img.shields.io/badge/Status-Active-brightgreen" alt="Status"></a>
</p>

---

## 📋 Overview

**Medical Receptionist** is a comprehensive clinic management system built with **Laravel 11** and **n8n automation**. It streamlines patient management, appointment scheduling, and automated patient communications through AI-powered chat assistants.

### Key Capabilities
- 👥 **Patient Management** - Complete patient profiles with contact details and medical notes
- 📅 **Smart Scheduling** - Appointment booking with Google Calendar sync
- 🤖 **AI Chat Assistant** - Automated patient conversations via n8n webhooks
- 🌍 **Multi-Language Support** - Arabic, French, and English interfaces
- 🔐 **Role-Based Access** - Admin and Receptionist user roles
- 📊 **Data Export** - Export patients and appointments to CSV
- 🔄 **Airtable Integration** - Flexible cloud-based data source with offline fallback

---

## 🏗️ Architecture

### Tech Stack

| Component | Technology |
|-----------|-----------|
| **Framework** | Laravel 11 |
| **Language** | PHP 8.2+ |
| **Frontend** | Vue.js / Vite |
| **Database** | SQLite (local) / MySQL / PostgreSQL |
| **Data Source** | Airtable API |
| **Automation** | n8n Webhooks |
| **Calendar** | Google Calendar API |
| **Auth** | Laravel Sanctum + Custom Airtable Provider |
| **Testing** | PHPUnit 10.5 |

### Data Flow

```
┌─────────────────┐
│   Web Browser   │
└────────┬────────┘
         │
    ┌────▼─────────┐
    │  Laravel App │
    └────┬─────────┘
         │
    ┌────┴──────────┬──────────────┬────────────┐
    │               │              │            │
 ┌──▼──┐      ┌────▼─────┐   ┌───▼──┐   ┌───▼───┐
 │Local│      │ Airtable  │   │ n8n  │   │Google │
 │ DB  │      │   API     │   │Chat  │   │ Cal   │
 └─────┘      └───────────┘   └──────┘   └───────┘
```

---

## 🚀 Quick Start

### Prerequisites
- **PHP 8.2+** with extensions: `pdo`, `gd`, `zip`, `curl`, `mbstring`
- **Composer** (PHP dependency manager)
- **Node.js 18+** with npm/yarn
- **Git**
- **Airtable Account** (for data backend) - [Sign up free](https://airtable.com)
- **n8n Instance** (for chat automation) - [Self-hosted or cloud](https://n8n.io)

### Installation

#### 1. Clone Repository
```bash
git clone https://github.com/yourusername/medical-receptionist.git
cd medical-receptionist
```

#### 2. Install PHP Dependencies
```bash
composer install
```

#### 3. Install Frontend Dependencies
```bash
npm install
```

#### 4. Configure Environment
```bash
cp .env.example .env
```

Edit `.env` and set:
```env
APP_NAME="Medical Receptionist"
APP_ENV=local
APP_KEY=  # Leave empty, will generate
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Airtable Configuration
AIRTABLE_TOKEN=your_airtable_pat
AIRTABLE_BASE_ID=your_base_id

# n8n Configuration
N8N_WEBHOOK_URL=https://your-n8n-instance.com/webhook/patient-chat

# Google Calendar (Optional)
GOOGLE_CALENDAR_KEY=your_google_api_key
```

#### 5. Generate App Key
```bash
php artisan key:generate
```

#### 6. Run Migrations
```bash
php artisan migrate
```

#### 7. Build Frontend Assets
```bash
npm run build
```

#### 8. Start Development Server
```bash
php artisan serve
```

Access the app at `http://localhost:8000`

---

## 🔧 Configuration

### Airtable Setup

1. **Create Airtable Base** with these tables:
   - `users` - Staff accounts
   - `patients` - Patient records
   - `appointments` - Scheduling data
   - `conversations` - Chat history
   - `settings` - App configuration

2. **Generate Personal Access Token**:
   - Go to Airtable Account Settings → Tokens
   - Create new token with `data.records:read`, `data.records:write` scopes
   - Copy token to `.env` as `AIRTABLE_TOKEN`

3. **Get Base ID**:
   - Open your base in Airtable
   - Base ID is in the URL: `https://airtable.com/`**`appXXXXXXXXXX`**`/...`

### n8n Webhook Configuration

1. Create a new n8n workflow
2. Add **Webhook** trigger node (listen on POST)
3. Add your chat automation nodes (e.g., OpenAI for responses)
4. Copy webhook URL to `.env` as `N8N_WEBHOOK_URL`

Example workflow trigger:
```json
{
  "patient_id": "rec123...",
  "message": "I need an appointment",
  "language": "en"
}
```

---

## 📖 Usage

### User Roles

#### Admin
- Manage users and roles
- Configure system settings
- View all patients and appointments
- Access admin dashboard

#### Receptionist
- Manage assigned patients
- Schedule appointments
- View conversations
- Update patient notes

### Core Features

#### Patient Management
```bash
# Access at: /patients
GET    /api/patients              # List all patients
POST   /api/patients              # Create patient
GET    /api/patients/{id}         # View patient details
PUT    /api/patients/{id}         # Update patient
DELETE /api/patients/{id}         # Archive patient
```

#### Appointments
```bash
# Access at: /appointments
GET    /api/appointments          # List appointments
POST   /api/appointments          # Create appointment
PATCH  /api/appointments/{id}     # Update appointment
PATCH  /api/appointments/{id}/status  # Change status
DELETE /api/appointments/{id}     # Cancel appointment
```

#### Chat & Conversations
```bash
# Access at: /chat
GET    /api/conversations         # List conversations
POST   /api/chat/send            # Send message (triggers n8n)
GET    /api/conversations/{id}    # View conversation
```

---

## 📊 Database Schema

### Patients Table
```sql
id (UUID)
patient_uid (unique identifier)
full_name
phone
email
language (ar, fr, en)
notes (LONGTEXT)
created_at, updated_at
```

### Appointments Table
```sql
id (UUID)
patient_id (FK)
appointment_date
appointment_time
consultation_type (general, dentaire, autre)
status (confirme, en_attente, annule)
google_calendar_event_id
created_at, updated_at
```

### Conversations Table
```sql
id (UUID)
patient_id (FK, nullable)
language
messages (JSON)
status (active, cloturee)
created_at, updated_at
```

### Settings Table
```sql
id (UUID)
key (unique)
value (LONGTEXT)
created_at, updated_at
```

---

## 🔐 Authentication

The app uses:
- **Laravel Sanctum** for API token authentication
- **Custom Airtable User Provider** for staff authentication
- **Session-based** for web routes

Default login page: `/login`

---

## 🧪 Testing

### Run Tests
```bash
php artisan test
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Test Configuration
See `phpunit.xml` for test database and environment setup.

---

## 🗂️ Project Structure

```
app/
├── Auth/
│   └── AirtableUserProvider.php      # Custom authentication
├── Http/
│   ├── Controllers/                  # Request handlers
│   ├── Middleware/                   # Route protection
│   └── Requests/                     # Form validation
├── Models/
│   ├── User.php, Patient.php, etc.  # Data models
│   └── Concerns/                     # Shared traits
├── Repositories/                     # Data access layer
├── Services/
│   ├── AirtableClient.php           # Airtable API wrapper
│   └── ...
└── Support/
    ├── AirtableFieldMap.php         # Field mapping
    ├── DoctorScope.php              # Authorization
    └── ...

routes/
├── web.php                          # Web routes
└── console.php                      # Artisan commands

database/
├── migrations/                      # Schema changes
├── factories/                       # Test data
└── seeders/                         # Initial data

resources/
├── views/                           # Blade templates
├── js/                              # Vue components
└── css/                             # Stylesheets

config/
├── app.php, auth.php, etc.          # App configuration
└── services.php                     # External APIs
```

---

## 🚨 Common Issues

### Airtable Connection Error
```
Solution: Verify AIRTABLE_TOKEN and AIRTABLE_BASE_ID in .env
Check token has correct scopes: data.records:read, data.records:write
```

### n8n Webhook Not Triggering
```
Solution: Check N8N_WEBHOOK_URL is publicly accessible
Verify webhook is active in n8n workflow
Test webhook with: curl -X POST {N8N_WEBHOOK_URL} -d '{"test":"data"}'
```

### SQLite Database Locked
```
Solution: Check file permissions on database/database.sqlite
Ensure storage/ directory is writable: chmod -R 775 storage/
```

---

## 📝 Environment Variables

Key configuration options:

```env
# Application
APP_NAME=Medical Receptionist
APP_ENV=local|production
APP_DEBUG=true|false
APP_KEY=base64:...

# Database (Local)
DB_CONNECTION=sqlite|mysql|pgsql
DB_DATABASE=database.sqlite

# External APIs
AIRTABLE_TOKEN=pat_xxxxx
AIRTABLE_BASE_ID=appxxxxx
N8N_WEBHOOK_URL=https://...
GOOGLE_CALENDAR_KEY=xxxxx

# Mail (Optional)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
```

---

## 🔄 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate strong `APP_KEY`
- [ ] Use environment-specific database (MySQL/PostgreSQL)
- [ ] Set up SSL/HTTPS
- [ ] Configure Airtable production base
- [ ] Test n8n webhook connectivity
- [ ] Set up logs and monitoring
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`

### Docker Deployment
```bash
# Build image
docker build -t medical-receptionist .

# Run container
docker run -e APP_KEY=... -e AIRTABLE_TOKEN=... medical-receptionist
```

---

## 🐛 Debugging

### Enable Debug Mode
```env
APP_DEBUG=true
```

### View Logs
```bash
tail -f storage/logs/laravel.log
```

### Database Queries
```bash
php artisan tinker
>>> \DB::enableQueryLog();
>>> // Run your code
>>> \DB::getQueryLog();
```

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards
- Follow PSR-12 PHP coding standard
- Add tests for new features
- Update documentation

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 💬 Support

- **Documentation**: See [user-table-structure.md](user-table-structure.md)
- **Issues**: Report bugs on GitHub Issues
- **Email**: contact@example.com
- **n8n Docs**: https://docs.n8n.io
- **Airtable API**: https://airtable.com/api

---

## 🙏 Acknowledgments

Built with:
- [Laravel](https://laravel.com) - PHP Framework
- [Airtable](https://airtable.com) - Cloud Database
- [n8n](https://n8n.io) - Open-source Automation
- [Vue.js](https://vuejs.org) - Frontend Framework

---

**Last Updated**: June 2024 | **Version**: 1.0.0
