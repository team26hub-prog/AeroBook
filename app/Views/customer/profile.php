<?php
declare(strict_types=1);
?>
<link rel="stylesheet" href="/assets/css/profile.css">
<section class="customer-profile" aria-labelledby="profile-heading">
    <div class="customer-section-heading">
        <div><p class="customer-eyebrow">Your account</p><h1 id="profile-heading">Profile / Account</h1><p>Your personal details and travel essentials, together in one place.</p></div>
    </div>
    <?php if($profile!==null): ?>
    <article class="profile-card profile-summary">
        <span class="profile-avatar" aria-hidden="true"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg></span>
        <div><p class="customer-eyebrow">AeroBook traveler</p><h2><?= $e($profile['full_name']) ?></h2><p><?= $e($profile['email']) ?></p></div>
        <span class="profile-status"><?= $e(ucfirst($profile['status'])) ?> account</span>
    </article>
    <article class="profile-card">
        <h2>Personal details</h2><p class="profile-description">The information associated with your AeroBook account.</p>
        <dl class="profile-details">
            <div><dt>Full name</dt><dd><?= $e($profile['full_name']) ?></dd></div>
            <div><dt>Email address</dt><dd><?= $e($profile['email']) ?></dd></div>
            <div><dt>Phone number</dt><dd><?= $e($profile['phone'] ?: 'Not provided') ?></dd></div>
            <div><dt>Account type</dt><dd><?= $e(ucfirst($profile['role'])) ?></dd></div>
            <div><dt>Member since</dt><dd><?= $e(date('F j, Y', strtotime($profile['created_at']))) ?></dd></div>
            <div><dt>Account status</dt><dd><?= $e(ucfirst($profile['status'])) ?></dd></div>
        </dl>
    </article>
    <?php endif ?>
    <div class="profile-grid">
        <article class="profile-card">
            <h2>Your travel account</h2><p class="profile-description">Continue planning a trip or review the details of an existing booking.</p>
            <nav class="profile-links" aria-label="Travel account shortcuts">
                <a href="/account?section=bookings"><strong>My bookings</strong><span>Review itineraries, passengers, and booking status.</span></a>
                <a href="/payments"><strong>Payments</strong><span>Submit payment details and track verification.</span></a>
                <a href="/account?section=tickets"><strong>E-tickets</strong><span>View and print tickets after payment verification.</span></a>
                <a href="/flights"><strong>Search flights</strong><span>Find an available flight for your next journey.</span></a>
            </nav>
        </article>
        <article class="profile-card">
            <h2>Account security</h2><p class="profile-description">Keep your account and travel information safe.</p>
            <ul class="profile-tips"><li>Keep your password private and use a unique password for your account.</li><li>Sign out when using a shared or public device.</li><li>Review passenger names and travel documents before confirming a booking.</li></ul>
            <form method="post" action="/logout" data-confirm="You will need to sign in again to access your account." data-confirm-title="Sign out?" data-confirm-button="Sign out">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="profile-signout" type="submit">Sign out of your account</button>
            </form>
        </article>
    </div>
</section>
