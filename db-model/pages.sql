INSERT INTO `page` (`code`, `created_at`, `updated_at`) VALUES
('staff', NOW(), NOW()),
('rules', NOW(), NOW()),
('ranks', NOW(), NOW()),
('school', NOW(), NOW());

INSERT INTO page_content (page_id, language, title, content_md, created_at, updated_at)
VALUES (
    (SELECT id FROM page WHERE code = 'staff'),
    'en',
    'Our Staff',
'# Staff

Meet the team behind our virtual airline. Dedicated professionals working every day to keep our operations running smoothly.

---

### John M. Carter
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

### Emily R. Stone
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

### Michael D. Harris
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

### Laura S. Bennett
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

