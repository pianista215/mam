INSERT INTO `page` (`code`, `created_at`, `updated_at`) VALUES
('staff', NOW(), NOW()),
('rules', NOW(), NOW()),
('ranks', NOW(), NOW()),
('school', NOW(), NOW()),
('home', NOW(), NOW()),
('registration_closed', NOW(), NOW());

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



