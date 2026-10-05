<?php
/**
 * UTF-8 Encoding Diagnostic Script
 * Run from command line: php check-utf8.php
 */

echo "\n";
echo "🔍 Nere Mining UTF-8 Encoding Diagnostic\n";
echo "=========================================\n\n";

$issues = [];
$passed = [];

// Check 1: Middleware file exists
echo "1️⃣  Checking EnsureUtf8Response middleware...\n";
if (file_exists('app/Http/Middleware/EnsureUtf8Response.php')) {
    echo "  ✓ Middleware file exists\n";
    $passed[] = 'Middleware exists';
} else {
    echo "  ❌ Middleware file MISSING\n";
    $issues[] = 'Middleware file not found - run git pull';
}

// Check 2: Migration file exists
echo "\n2️⃣  Checking UTF-8 migration...\n";
$migrations = glob('database/migrations/*fix_intranet_utf8*');
if (!empty($migrations)) {
    echo "  ✓ Migration file exists: " . basename($migrations[0]) . "\n";
    $passed[] = 'Migration exists';
} else {
    echo "  ⚠️  Migration not found (optional)\n";
}

// Check 3: Blade files contain correct UTF-8
echo "\n3️⃣  Checking blade files for French accents...\n";
$bladeFiles = array_merge(
    glob('resources/views/intranet/*.blade.php'),
    glob('resources/views/intranet/*/*.blade.php'),
    glob('resources/views/intranet/*/*/*.blade.php')
);
$frenchWords = ['Créer', 'Département', 'Sûreté', 'Approvisionnement', 'Sélectionnez'];
$foundCount = 0;

foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    foreach ($frenchWords as $word) {
        if (strpos($content, $word) !== false) {
            $foundCount++;
            break;
        }
    }
}

if ($foundCount > 0) {
    echo "  ✓ French accents found in blade files ($foundCount files)\n";
    $passed[] = 'French text in views OK';
} else {
    echo "  ⚠️  No French accents detected in blade files\n";
}

// Check 4: PHP encoding
echo "\n4️⃣  Checking PHP/PHP-FPM encoding...\n";
$phpCharset = ini_get('default_charset');
if (strtolower($phpCharset) === 'utf-8') {
    echo "  ✓ PHP default_charset: $phpCharset\n";
    $passed[] = 'PHP encoding OK';
} else {
    echo "  ⚠️  PHP default_charset: $phpCharset (should be UTF-8)\n";
    $issues[] = 'PHP default_charset not UTF-8 - add to php.ini: default_charset = "UTF-8"';
}

// Check 5: Laravel config
echo "\n5️⃣  Checking Laravel configuration...\n";
try {
    // Load composer autoloader first
    require 'vendor/autoload.php';
    
    // Check if middleware is registered
    if (class_exists('App\Http\Middleware\EnsureUtf8Response')) {
        echo "  ✓ Middleware class can be loaded\n";
        $passed[] = 'Middleware class loads OK';
    } else {
        echo "  ❌ Middleware class cannot be loaded\n";
        $issues[] = 'Middleware class not found - run composer dump-autoload';
    }
} catch (Exception $e) {
    echo "  ⚠️  Could not check Laravel config: " . $e->getMessage() . "\n";
}

// Check 6: Test file encoding
echo "\n6️⃣  Checking source file encoding...\n";
$testFile = 'app/Http/Middleware/EnsureUtf8Response.php';
if (file_exists($testFile)) {
    $content = file_get_contents($testFile);
    if (mb_check_encoding($content, 'UTF-8')) {
        echo "  ✓ $testFile is valid UTF-8\n";
        $passed[] = 'File encoding OK';
    } else {
        echo "  ❌ $testFile is NOT valid UTF-8\n";
        $issues[] = "$testFile has invalid UTF-8 encoding";
    }
}

// Check 7: Test blade file encoding
echo "\n7️⃣  Checking blade file encoding...\n";
$bladeTest = 'resources/views/intranet/home.blade.php';
if (file_exists($bladeTest)) {
    $content = file_get_contents($bladeTest);
    if (mb_check_encoding($content, 'UTF-8')) {
        echo "  ✓ $bladeTest is valid UTF-8\n";
        // Also check for specific accented words
        if (strpos($content, 'Département') !== false) {
            echo "  ✓ Contains 'Département' (accent preserved)\n";
            $passed[] = 'Blade content encoding OK';
        } else {
            echo "  ⚠️  Could not find 'Département' in blade file\n";
        }
    } else {
        echo "  ❌ $bladeTest is NOT valid UTF-8\n";
        $issues[] = "$bladeTest has invalid UTF-8 encoding";
    }
}

// Summary
echo "\n";
echo "═══════════════════════════════════════\n";
echo "📋 Diagnostic Summary\n";
echo "═══════════════════════════════════════\n\n";

if (!empty($passed)) {
    echo "✅ Passed checks (" . count($passed) . "):\n";
    foreach ($passed as $check) {
        echo "   • $check\n";
    }
}

if (!empty($issues)) {
    echo "\n❌ Issues found (" . count($issues) . "):\n";
    foreach ($issues as $issue) {
        echo "   • $issue\n";
    }
    
    echo "\n💡 Quick fixes:\n";
    echo "   1. Run: git pull origin main\n";
    echo "   2. Run: composer dump-autoload -o\n";
    echo "   3. Run: php artisan config:cache\n";
    echo "   4. Run: php artisan route:cache\n";
    echo "   5. Run: php artisan cache:clear\n";
} else {
    echo "\n🎉 All checks passed! UTF-8 encoding should be working correctly.\n";
}

echo "\n";
