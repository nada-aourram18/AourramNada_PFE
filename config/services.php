<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL'),
    ],

    'airtable' => [
        // README historically used AIRTABLE_TOKEN; prefer AIRTABLE_API_KEY.
        'api_key' => env('AIRTABLE_API_KEY', env('AIRTABLE_TOKEN')),
        'base_id' => env('AIRTABLE_BASE_ID'),
        /** À mettre à false uniquement en local si erreur SSL (ex. Windows sans bundle CA). */
        'http_verify' => filter_var(env('AIRTABLE_HTTP_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
        /*
         * Noms exacts des tables dans Airtable (comme dans l’UI).
         * Clés logiques utilisées dans le code : appointments, patients, settings, users.
         */
        'tables' => [
            'appointments' => env('AIRTABLE_TABLE_APPOINTMENTS', 'Appointments'),
            'patients' => env('AIRTABLE_TABLE_PATIENTS', 'Patients'),
            /* Vide = pas de table Settings (paramètres via .env uniquement). */
            'settings' => env('AIRTABLE_TABLE_SETTINGS', ''),
            'users' => env('AIRTABLE_TABLE_USERS', 'Doctors'),
            /* Vide = pas de table Conversations dans Airtable (chat = session serveur uniquement). */
            'conversations' => env('AIRTABLE_TABLE_CONVERSATIONS', ''),
        ],
        /*
         * Mapping attribut logique → nom exact de colonne Airtable (base nada_n8n).
         */
        'patient_columns' => [
            'patient_uid' => env('AIRTABLE_FIELD_PATIENT_UID', 'patient_id'),
            'full_name' => env('AIRTABLE_FIELD_PATIENT_NAME', 'Name'),
            'phone' => env('AIRTABLE_FIELD_PATIENT_PHONE', 'phone_number'),
            'email' => env('AIRTABLE_FIELD_PATIENT_EMAIL', ''),
            'language' => env('AIRTABLE_FIELD_PATIENT_LANGUAGE', ''),
            'notes' => env('AIRTABLE_FIELD_PATIENT_NOTES', 'notes'),
        ],
        'user_columns' => [
            'name' => env('AIRTABLE_FIELD_USER_NAME', 'full_name'),
            'email' => env('AIRTABLE_FIELD_USER_EMAIL', 'email'),
            'password' => env('AIRTABLE_FIELD_USER_PASSWORD', 'password'),
            'phone' => env('AIRTABLE_FIELD_USER_PHONE', 'phone_number'),
            'clinic_name' => env('AIRTABLE_FIELD_USER_CLINIC', 'clinic_name'),
            'specialty' => env('AIRTABLE_FIELD_USER_SPECIALTY', 'specialty'),
        ],
        /*
         * Noms des champs dans Airtable (liaison RDV → patient, etc.)
         */
        'fields' => [
            /* Vide = détection auto (lien vers la table Patients). Sinon nom exact du champ dans Appointments. */
            'appointment_patient_link' => env('AIRTABLE_FIELD_APPOINTMENT_PATIENT', ''),
            /* Lien Appointments → Doctors (médecin propriétaire du RDV). */
            'appointment_doctor' => env('AIRTABLE_FIELD_APPOINTMENT_DOCTOR', 'doctor_id'),
            /* Lien Patients → Doctors (optionnel). Vide = patients filtrés via les RDV. */
            'patient_doctor' => env('AIRTABLE_FIELD_PATIENT_DOCTOR', ''),
            /* Vide = pas de colonne « role » dans Airtable (admin = e-mails ci‑dessous). */
            'user_role' => env('AIRTABLE_FIELD_USER_ROLE', ''),
        ],
        /*
         * Noms exacts des colonnes table Appointments (vide = auto : libellés FR courants puis noms anglais du code).
         */
        'appointment_column_overrides' => [
            'appointment_date' => env('AIRTABLE_FIELD_APPOINTMENT_DATE', ''),
            'appointment_time' => env('AIRTABLE_FIELD_APPOINTMENT_TIME', ''),
            'appointment_uid' => env('AIRTABLE_FIELD_APPOINTMENT_UID', ''),
            'consultation_type' => env('AIRTABLE_FIELD_APPOINTMENT_CONSULTATION_TYPE', ''),
            'status' => env('AIRTABLE_FIELD_APPOINTMENT_STATUS', ''),
            'google_calendar_event_id' => env('AIRTABLE_FIELD_APPOINTMENT_GOOGLE_CALENDAR', ''),
        ],
        /** E-mails considérés comme admin si le champ role n’existe pas dans Airtable. */
        'admin_emails' => array_values(array_filter(array_map('trim', explode(',', env('AIRTABLE_ADMIN_EMAILS', 'admin@clinic.com'))))),
        /*
         * Champs users envoyés à Airtable (noms exacts des colonnes). Par défaut le minimum.
         * Ex. pour une base complète : name,email,password,phone,clinic_name,avatar_path,theme,remember_token
         */
        'user_sync_fields' => array_values(array_filter(array_map('trim', explode(',', env('AIRTABLE_USER_SYNC_FIELDS', 'name,email,password,phone,clinic_name,avatar_path,remember_token'))))),
    ],

];
