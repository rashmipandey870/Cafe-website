<?php
/**
 * about.php
 * About Us Page - Café Philosophy, Ingredient Quality, and Story
 */

$page_title = 'Our Story';
$page_description = 'Learn about Mellow & Meadow—our passion for single-origin coffees, organic sourcing, artisanal sourdough, and the story behind our cozy sunlit sanctuary.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- About Hero -->
<section class="section-padding" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="section-tagline mb-2 d-inline-block">Philosophy</span>
        <h1 class="display-3 display-font mb-4">Born from a love of slow mornings.</h1>
        <p class="lead text-muted mx-auto" style="max-width: 800px; font-size: 1.25rem;">
            At Mellow & Meadow, we believe that breakfast and coffee shouldn't be rushed. We craft spaces filled with sunlight, plants, and high-quality, local food to help you slow down.
        </p>
    </div>
</section>

<!-- Our Story Editorial -->
<section class="section-padding">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="section-tagline mb-2 d-inline-block">The Journey</span>
                <h2 class="display-font display-4 mb-4" style="color: var(--accent-coffee);">How Mellow & Meadow Bloomed</h2>
                <p class="text-muted">
                    We started in early 2026 with a simple dream: to create a specialty café that feels like stepping into a greenhouse. The name <strong>Mellow & Meadow</strong> reflects this intent—a mellow, comforting atmosphere paired with the freshness of a wild meadow.
                </p>
                <p class="text-muted">
                    What began as a tiny coffee stand has grown into a neighborhood sanctuary. We partner directly with micro-roasters who select unique single-origin coffee crops from small estates. We also work with local farmers who supply us with heritage vegetables, organic eggs, and raw honey.
                </p>
                <p class="text-muted">
                    Whether you are starting your morning with our Sage Honey Cortado, enjoying a weekend family brunch, or typing away at your laptop, you are part of our story.
                </p>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&q=80&w=700" alt="Fresh pastries and coffees on table" class="img-fluid rounded shadow-sm" style="border-radius: var(--border-radius-md) !important; width: 100%; max-height: 500px; object-fit: cover;">
            </div>
        </div>
    </div>
</section>

<!-- Ingredient Focus Section -->
<section class="section-padding bg-cream">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tagline mb-2 d-inline-block">Quality Matters</span>
            <h2 class="section-title">What Makes Our Plates Special</h2>
        </div>
        
        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-md-4">
                <div class="card border-0 bg-transparent h-100 p-3">
                    <div class="mb-3 text-success" style="color: var(--accent-sage) !important; font-size: 2.5rem;">
                        <i class="bi bi-tree"></i>
                    </div>
                    <h3 class="display-font h3 text-dark">Locally Sourced</h3>
                    <p class="text-muted">
                        Our heirloom tomatoes, microgreens, eggs, and dairy are sourced weekly from family-owned organic farms in the outskirts of the NCR, ensuring every bite supports local growers and tastes clean.
                    </p>
                </div>
            </div>
            
            <!-- Feature 2 -->
            <div class="col-md-4">
                <div class="card border-0 bg-transparent h-100 p-3">
                    <div class="mb-3 text-success" style="color: var(--accent-sage) !important; font-size: 2.5rem;">
                        <i class="bi bi-cup-hot"></i>
                    </div>
                    <h3 class="display-font h3 text-dark">Specialty Grade Coffee</h3>
                    <p class="text-muted">
                        Our coffee beans score above 84 points on the specialty scale. They are hand-picked, washed, and light-to-medium roasted to maintain individual origin notes of peach, lavender, and hazelnut.
                    </p>
                </div>
            </div>
            
            <!-- Feature 3 -->
            <div class="col-md-4">
                <div class="card border-0 bg-transparent h-100 p-3">
                    <div class="mb-3 text-success" style="color: var(--accent-sage) !important; font-size: 2.5rem;">
                        <i class="bi bi-brightness-high"></i>
                    </div>
                    <h3 class="display-font h3 text-dark">Artisanal Bakery</h3>
                    <p class="text-muted">
                        We bake in small batches. Our signature sourdough undergoes a 36-hour cold fermentation process, creating bread that is easy on digestion and crisp in texture.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Simple Team / Community Callout -->
<section class="section-padding text-center">
    <div class="container">
        <span class="section-tagline mb-2 d-inline-block">The Team</span>
        <h2 class="section-title">Brewing warmth, every single day</h2>
        <p class="text-muted mx-auto mb-5" style="max-width: 600px;">
            Behind the espresso machines and the baking trays are a group of baristas and chefs who love what they do. We are here to make your day just a little bit brighter.
        </p>
        
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300" alt="Head Chef" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <h5 class="mb-1 text-dark display-font">Nisha Sen</h5>
                <p class="text-muted small">Head Pastry Chef</p>
            </div>
            <div class="col-6 col-md-3">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=300" alt="Lead Barista" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <h5 class="mb-1 text-dark display-font">Tara Mehta</h5>
                <p class="text-muted small">Lead Barista</p>
            </div>
            <div class="col-6 col-md-3">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=300" alt="Manager" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <h5 class="mb-1 text-dark display-font">Amit Verma</h5>
                <p class="text-muted small">Café Curator</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
