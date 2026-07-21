<?php

return [
    'patient_created' => 'Patient créé avec succès.',
    'patient_updated' => 'Patient mis à jour avec succès.',
    'patient_deleted' => 'Patient supprimé avec succès.',
    'appointment_created' => 'Rendez-vous créé avec succès.',
    'appointment_updated' => 'Rendez-vous mis à jour avec succès.',
    'appointment_deleted' => 'Rendez-vous supprimé avec succès.',
    'appointment_status_updated' => 'Statut du rendez-vous mis à jour.',
    'settings_saved' => 'Paramètres enregistrés.',
    'receptionist_created' => 'Compte réceptionniste créé.',
    'receptionist_deleted' => 'Compte réceptionniste supprimé.',
    'n8n_not_configured' => 'L’URL du webhook n8n n’est pas configurée.',
    'n8n_unreachable' => 'Impossible de joindre le service d’automatisation.',
    'n8n_error_status' => 'Le service d’automatisation a renvoyé une erreur (HTTP :status).',
    'empty_agent_reply' => '(Aucune réponse)',
    'unknown_patient' => 'Patient inconnu',
    'new_patient' => 'Nouveau patient',
    'edit' => 'Modifier',
    'detail' => 'Voir détail',
    'delete' => 'Supprimer',
    'save' => 'Enregistrer',
    'cancel' => 'Annuler',
    'filters' => 'Filtres',
    'export_csv' => 'Export CSV',
    'new_appointment' => 'Nouveau rendez-vous',
    'add_appointment_for_patient' => 'Ajouter un rendez-vous pour ce patient',
    'select_patient' => 'Sélectionner un patient…',

    'app' => [
        'tagline' => 'Plateforme clinique',
    ],

    'auth' => [
        'badge' => 'Plateforme sécurisée',
        'hero_text' => 'Centralisez patients, rendez-vous et conversations IA pour une expérience soignée et moderne.',
        'photo_credit' => 'Visuel professionnel — Unsplash',
    ],

    'nav' => [
        'dashboard' => 'Tableau de bord',
        'patients' => 'Patients',
        'appointments' => 'Rendez-vous',
        'calendar' => 'Calendrier',
        'conversations' => 'Conversations',
        'profile' => 'Mon profil',
        'settings' => 'Paramètres',
        'logout' => 'Déconnexion',
        'chat_public' => 'Chat patient',
    ],

    'profile_updated' => 'Profil mis à jour avec succès.',
    'theme_updated' => 'Thème enregistré.',

    'theme' => [
        'light' => 'Mode clair',
        'dark' => 'Mode sombre',
        'light_short' => 'Clair',
        'dark_short' => 'Sombre',
    ],

    'dashboard' => [
        'subtitle' => 'Vue d’ensemble de l’activité du jour et du mois.',
        'kpi_today' => 'Rendez-vous aujourd’hui',
        'kpi_month_patients' => 'Nouveaux patients (mois)',
        'kpi_active_conv' => 'Conversations actives',
        'kpi_status' => 'RDV par statut',
        'chart_title' => 'Activité (7 derniers jours)',
        'latest' => 'Derniers rendez-vous',
        'no_appointments' => 'Aucun rendez-vous récent.',
    ],

    'profile' => [
        'subtitle' => 'Modifiez vos informations, votre photo et votre mot de passe.',
        'theme_section' => 'Apparence',
        'theme_hint' => 'Interface en noir et blanc — choisissez le mode clair ou sombre.',
        'avatar_section' => 'Photo de profil',
        'avatar_hint' => 'Optionnel — JPG, PNG ou WebP, max 2 Mo. Carrée de préférence.',
        'avatar_choose' => 'Choisir une image',
        'avatar_remove' => 'Supprimer la photo actuelle',
        'password_section' => 'Changer le mot de passe',
        'new_password' => 'Nouveau mot de passe',
        'password_hint' => 'Laissez vide pour conserver le mot de passe actuel.',
        'save' => 'Enregistrer le profil',
    ],

    'status' => [
        'confirme' => 'Confirmé',
        'en_attente' => 'En attente',
        'annule' => 'Annulé',
    ],

    'consultation' => [
        'general' => 'Général',
        'dentaire' => 'Dentaire',
        'autre' => 'Autre',
    ],

    'conversation_status' => [
        'active' => 'Active',
        'cloturee' => 'Clôturée',
    ],

    'lang' => [
        'ar' => 'Arabe',
        'fr' => 'Français',
        'en' => 'Anglais',
    ],

    'login' => [
        'title' => 'Connexion',
        'email' => 'E-mail',
        'password' => 'Mot de passe',
        'remember' => 'Se souvenir de moi',
        'submit' => 'Se connecter',
        'no_account' => 'Pas encore de compte ?',
    ],

    'register' => [
        'title' => 'Créer un compte',
        'subtitle' => 'Compte réceptionniste — accès au tableau de bord',
        'name' => 'Nom complet',
        'phone' => 'Numéro de téléphone',
        'clinic_name' => 'Nom du cabinet',
        'specialty' => 'Spécialité',
        'specialty_placeholder' => 'Ex. Cardiologie, Dentaire…',
        'password_confirmation' => 'Confirmer le mot de passe',
        'submit' => 'S’inscrire',
        'already_account' => 'Déjà un compte ?',
        'success' => 'Bienvenue ! Votre compte a été créé.',
    ],

    'chat' => [
        'title' => 'Assistant médical',
        'subtitle' => 'Posez votre question — nous répondons dans votre langue.',
        'placeholder' => 'Écrivez votre message…',
        'send' => 'Envoyer',
        'typing' => 'L’assistant répond…',
    ],
];
