SET NAMES 'utf8mb4';

INSERT INTO `page` (`code`, `public`, `created_at`, `updated_at`) VALUES
('staff', 1, NOW(), NOW()),
('rules', 1, NOW(), NOW()),
('ranks', 1, NOW(), NOW()),
('school', 1, NOW(), NOW()),
('home', 1, NOW(), NOW()),
('registration_closed', 1, NOW(), NOW());

INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'staff'),
    'en',
    'Our Staff',
'
Meet the team behind our virtual airline. Dedicated professionals working every day to keep our operations running smoothly.

---

### John M. C.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/john.jpeg" alt="John Carter" width="150">
    <div>
        <strong>Title:</strong> Chief Executive Officer (CEO)<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>License:</strong> MAM101<br>
        <strong>Location:</strong> United Kingdom<br>
        <strong>Rank:</strong> Captain<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>

---

### Emily R. S.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/emily.jpeg" alt="Emily Stone" width="150">
    <div>
        <strong>Title:</strong> Human Resources Manager<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>License:</strong> MAM102<br>
        <strong>Location:</strong> Spain<br>
        <strong>Rank:</strong> First Officer<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>

---

### Michael D. H.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/michael.jpeg" alt="Michael Harris" width="150">
    <div>
        <strong>Title:</strong> Flight Training Director<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>License:</strong> MAM103<br>
        <strong>Location:</strong> Germany<br>
        <strong>Rank:</strong> Training Captain<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>

---

### Laura S. B.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/laura.jpeg" alt="Laura Bennett" width="150">
    <div>
        <strong>Title:</strong> Operations Coordinator<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>License:</strong> MAM104<br>
        <strong>Location:</strong> Italy<br>
        <strong>Rank:</strong> Senior First Officer<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>
',
    NOW(),
    NOW()
);

INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'staff'),
    'es',
    'Nuestra Directiva',
'
Conoce al equipo directivo detrás de nuestra aerolínea virtual. Profesionales dedicados que trabajan cada día para que nuestras operaciones funcionen sin problemas.

---

### John M. C.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/john.jpeg" alt="John Carter" width="150">
    <div>
        <strong>Título:</strong> Director Ejecutivo (CEO)<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>Licencia:</strong> MAM101<br>
        <strong>Ubicación:</strong> Reino Unido<br>
        <strong>Rango:</strong> Capitán<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>

---

### Emily R. S.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/emily.jpeg" alt="Emily Stone" width="150">
    <div>
        <strong>Título:</strong> Responsable de Recursos Humanos<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>Licencia:</strong> MAM102<br>
        <strong>Ubicación:</strong> España<br>
        <strong>Rango:</strong> Primer Oficial<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>

---

### Michael D. H.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/michael.jpeg" alt="Michael Harris" width="150">
    <div>
        <strong>Título:</strong> Director de Formación de Vuelo<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>Licencia:</strong> MAM103<br>
        <strong>Ubicación:</strong> Alemania<br>
        <strong>Rango:</strong> Capitán Instructor<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>

---

### Laura S. B.
<div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
    <img src="/uploads/staff/laura.jpeg" alt="Laura Bennett" width="150">
    <div>
        <strong>Título:</strong> Coordinadora de Operaciones<br>
        <strong>Email:</strong> executive@example.com<br>
        <strong>Licencia:</strong> MAM104<br>
        <strong>Ubicación:</strong> Italia<br>
        <strong>Rango:</strong> Primer Oficial Senior<br>
        <strong>IVAO VID:</strong> XXXXXXX<br>
        <strong>VATSIM VID:</strong> XXXXXX
    </div>
</div>
',
    NOW(),
    NOW()
);


INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'rules'),
    'en',
    'Rules',
'
# General Info
## Access information
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

# Registration
## Entry and registration
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
',
    NOW(),
    NOW()
);

INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'rules'),
    'es',
    'Reglas',
'
# Información General
## Información de acceso
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

# Registro
## Entrada y registro
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
',
    NOW(),
    NOW()
);


INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'ranks'),
    'en',
    'Ranks',
'
## Captain
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

## First Officer
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

## Pilot Student
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
',
    NOW(),
    NOW()
);

INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'ranks'),
    'es',
    'Rangos',
'
## Capitán
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

## Primer Oficial
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

## Piloto Estudiante
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
',
    NOW(),
    NOW()
);


INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'school'),
    'en',
    'School',
'
## About the school
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
',
    NOW(),
    NOW()
);

INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'school'),
    'es',
    'Escuela',
'
## Acerca de la escuela
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
',
    NOW(),
    NOW()
);


INSERT INTO page_content (page_id, language, title, content_md)
VALUES (
    (SELECT id FROM page WHERE code = 'home'),
    'en',
    'Welcome to Our Virtual Airline',
    '# Welcome to MAM Virtual Airlines

We are a passionate community of virtual aviation enthusiasts.


Fly with us, improve your skills, and enjoy realistic operations in Microsoft Flight Simulator, X-Plane and Prepar3D.
'
);

INSERT INTO page_content (page_id, language, title, content_md)
VALUES (
    (SELECT id FROM page WHERE code = 'home'),
    'es',
    'Bienvenido a Nuestra Aerolínea Virtual',
    '# Bienvenido a MAM Virtual Airlines

Somos una comunidad apasionada de entusiastas de la aviación virtual.

Vuela con nosotros, mejora tus habilidades y disfruta de operaciones realistas en Microsoft Flight Simulator, X-Plane y Prepar3D.
'
);


INSERT INTO `page_content` (`page_id`, `language`, `title`, `content_md`)
VALUES (
    (SELECT id FROM page WHERE code = 'registration_closed'),
    'en',
    'Registration Closed',
    'We appreciate your interest in joining **MAM Virtual Airlines**!

Our registration period is currently closed, but do not worry — we reopen registrations **periodically each year** to welcome new pilots into our community.
This helps us ensure a proper onboarding experience and high-quality support for every new member.

### When will registration reopen?

Registration windows are announced in advance on our official channels.
We invite you to stay tuned and check back regularly for updates.

If you have any questions in the meantime, feel free to **contact us through our social media channels**.
We are always happy to assist you.

Thank you for your patience and understanding.
**Clear skies and see you soon!**'
);

INSERT INTO `page_content` (`page_id`, `language`, `title`, `content_md`)
VALUES (
    (SELECT id FROM page WHERE code = 'registration_closed'),
    'es',
    'Registro Cerrado',
    '¡Agradecemos tu interés en unirte a **MAM Virtual Airlines**!

Nuestro periodo de registro está actualmente cerrado, pero no te preocupes: **reabrimos los registros periódicamente cada año** para dar la bienvenida a nuevos pilotos en nuestra comunidad.
Esto nos ayuda a garantizar una experiencia de incorporación adecuada y soporte de alta calidad para cada nuevo miembro.

### ¿Cuándo se reabrirá el registro?

Los periodos de registro se anuncian con antelación en nuestros canales oficiales.
Te invitamos a estar atento y visitar la página regularmente para obtener actualizaciones.

Si tienes alguna pregunta mientras tanto, no dudes en **contactarnos a través de nuestros canales en redes sociales**.
Siempre estamos felices de ayudarte.

Gracias por tu paciencia y comprensión.
**¡Cielos despejados y nos vemos pronto!**'
);




