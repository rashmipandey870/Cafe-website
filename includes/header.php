<?php
/**
 * includes/header.php
 * Public HTML Head, Dynamic SEO Meta, and Asset Includes
 */

// Load core configurations and libraries
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

// Build page titles and SEO descriptions dynamically
$site_name = isset($settings['cafe_name']) ? $settings['cafe_name'] : 'Mellow & Meadow';
$full_title = isset($page_title) ? $page_title . ' | ' . $site_name : $site_name . ' | Artisanal Specialty Café & Brunch';
$meta_desc = isset($page_description) ? $page_description : 'Welcome to ' . $site_name . ', a sun-filled, plant-rich specialty café serving organic coffee, floral tea, sourdough toasts, and artisanal desserts in New Delhi.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo escape($meta_desc); ?>">
    
    <!-- Open Graph (Facebook / Social Previews) -->
    <meta property="og:title" content="<?php echo escape($full_title); ?>">
    <meta property="og:description" content="<?php echo escape($meta_desc); ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/images/gal_interior.jpg">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo escape((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>">
    
    <title><?php echo escape($full_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <!-- Structured Data (JSON-LD Local Business Schema) for SEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "CafeOrCoffeeShop",
      "name": "<?php echo escape($site_name); ?>",
      "image": "<?php echo BASE_URL; ?>/assets/images/gal_interior.jpg",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "<?php echo escape($settings['cafe_address']); ?>",
        "addressLocality": "New Delhi",
        "addressCountry": "IN"
      },
      "telephone": "<?php echo escape($settings['cafe_phone']); ?>",
      "email": "<?php echo escape($settings['cafe_email']); ?>",
      "openingHours": "Mo-Su 08:00-22:00"
    }
    </script>
</head>
<body>
