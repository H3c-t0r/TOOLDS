<?php
session_start();

$tools_file = 'tools.json';
$default_structure = [
    'web' => ['tools' => [], 'useful_links' => []],
    'infra' => ['tools' => [], 'useful_links' => []]
];

if (!file_exists($tools_file)) {
    file_put_contents($tools_file, json_encode($default_structure));
}
$tools = json_decode(file_get_contents($tools_file), true);

// Ensure structure integrity
foreach ($default_structure as $category => $sections) {
    if (!isset($tools[$category])) {
        $tools[$category] = $sections;
    } else {
        foreach ($sections as $section => $items) {
            if (!isset($tools[$category][$section])) {
                $tools[$category][$section] = [];
            }
        }
    }
}

$allowed_categories = ['web', 'infra'];
$allowed_sections = ['tools', 'useful_links'];
$current_category = $_GET['category'] ?? 'web';
$current_section = $_GET['section'] ?? 'tools';

if (!in_array($current_category, $allowed_categories)) $current_category = 'web';
if (!in_array($current_section, $allowed_sections)) $current_section = 'tools';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category']) && isset($_POST['section']) && isset($_POST['tool_name']) && isset($_POST['tool_url'])) {
        $category = $_POST['category'];
        $section = $_POST['section'];
        $tool_name = htmlspecialchars($_POST['tool_name']);
        $tool_url = filter_var($_POST['tool_url'], FILTER_VALIDATE_URL);

        if ($tool_name && $tool_url) {
            $tools[$category][$section][] = ['name' => $tool_name, 'url' => $tool_url];
            file_put_contents($tools_file, json_encode($tools));
        }
    }

    if (isset($_POST['delete'])) {
        $category = $_POST['category'];
        $section = $_POST['section'];
        $index = $_POST['delete'];
        if (isset($tools[$category][$section][$index])) {
            array_splice($tools[$category][$section], $index, 1);
            file_put_contents($tools_file, json_encode($tools));
        }
    }
    
    header("Location: ".$_SERVER['PHP_SELF']."?category=$current_category&section=$current_section");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tools Dashboard</title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
@import url('https://unpkg.com/css.gg@2.0.0/icons/css/add.css');
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
    color: #ffffff;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    overflow-x: hidden;
}

.navbar {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding: 1rem;
    background: rgba(16, 16, 16, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-dropdown {
    position: relative;
    display: inline-block;
}

.dropbtn {
    background: transparent;
    color: rgba(255, 255, 255, 0.8);
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dropbtn:hover {
    background: rgba(255, 152, 0, 0.1);
    color: #ff9800;
}

.nav-dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: rgba(30, 30, 30, 0.98);
    backdrop-filter: blur(20px);
    min-width: 200px;
    border-radius: 8px;
    padding: 0.5rem 0;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    z-index: 1001;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-dropdown:hover .nav-dropdown-content {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

.nav-dropdown-content a {
    color: rgba(255, 255, 255, 0.8);
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    display: block;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    position: relative;
}

.nav-dropdown-content a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 3px;
    height: 100%;
    background: #ff9800;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.nav-dropdown-content a:hover {
    background: rgba(255, 152, 0, 0.05);
    color: #ff9800;
    padding-left: 2rem;
}

.nav-dropdown-content a:hover::before {
    opacity: 1;
}

.header {
    font-size: 2.8rem;
    font-weight: 700;
    text-align: center;
    margin: 3rem 0;
    background: linear-gradient(45deg, #ff9800, #ffc107);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    position: relative;
}

.header::after {
    content: '🚀';
    position: absolute;
    right: -50px;
    top: -20px;
    font-size: 1.8rem;
    filter: drop-shadow(0 0 8px rgba(255, 152, 0, 0.4));
}

.form-container {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 1.5rem;
    margin: 2rem auto;
    width: 80%;
    max-width: 800px;
    transition: all 0.3s ease;
}

.form-container:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

input[type="text"], 
input[type="url"] {
    background: rgba(40, 40, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.8rem 1.2rem;
    border-radius: 8px;
    color: white;
    flex: 1;
    min-width: 250px;
    transition: all 0.3s ease;
}

input:focus {
    border-color: rgba(255, 152, 0, 0.5);
    box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
}

button[type="submit"] {
    background: linear-gradient(135deg, #ff9800, #ff6b00);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(255, 107, 0, 0.2);
}

.tools-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.tool-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.tool-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
}

.tool-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 152, 0, 0.1), transparent);
    transform: rotate(45deg);
    pointer-events: none;
}

.tool-card:hover::before {
    animation: shine 1.5s;
}

.tool-card a {
    color: #ff9800;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    transition: all 0.3s ease;
}

.tool-card a:hover {
    color: #ffc107;
    transform: translateX(5px);
}

.delete-button {
    background: rgba(244, 67, 54, 0.15);
    color: #f44336;
    border: 1px solid rgba(244, 67, 54, 0.3);
    padding: 0.5rem 1rem !important;
    border-radius: 6px;
    margin-top: 1rem;
    transition: all 0.3s ease;
}

.delete-button:hover {
    background: rgba(244, 67, 54, 0.25);
    transform: scale(1.05);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes shine {
    0% { transform: rotate(45deg) translate(-50%, -50%); }
    100% { transform: rotate(45deg) translate(150%, 150%); }
}

@media (max-width: 768px) {
    .form-container {
        width: 90%;
        padding: 1rem;
    }
    
    form {
        flex-direction: column;
    }
    
    input[type="text"],
    input[type="url"] {
        width: 100%;
    }
}
</style>
</head>
<body>

<div class="header">🚀 Tools Dashboard</div>

    <div class="navbar">
        <div class="nav-dropdown">
            <button class="dropbtn">Web</button>
            <div class="nav-dropdown-content">
                <a href="?category=web&section=tools">Tools</a>
                <a href="?category=web&section=useful_links">Useful Links</a>
            </div>
        </div>
        <div class="nav-dropdown">
            <button class="dropbtn">Infra</button>
            <div class="nav-dropdown-content">
                <a href="?category=infra&section=tools">Tools</a>
                <a href="?category=infra&section=useful_links">Useful Links</a>
            </div>
        </div>
    </div>
    
    <div class="form-container">
        <form method="POST">
            <input type="hidden" name="category" value="<?= $current_category ?>">
            <input type="hidden" name="section" value="<?= $current_section ?>">
            <input type="text" name="tool_name" placeholder="Tool Name" required>
            <input type="url" name="tool_url" placeholder="Tool URL" required>
            <button type="submit">➕ Add Tool</button>
        </form>
    </div>

    <div class="tools-list">
        <?php if (!empty($tools[$current_category][$current_section])): ?>
            <?php foreach ($tools[$current_category][$current_section] as $index => $tool): ?>
                <div class="tool-card">
                    <div><strong><?= $tool['name'] ?></strong></div>
                    <a href="<?= $tool['url'] ?>" target="_blank">🔗 Visit</a>
                    <form method="POST">
                        <input type="hidden" name="category" value="<?= $current_category ?>">
                        <input type="hidden" name="section" value="<?= $current_section ?>">
                        <button type="submit" name="delete" value="<?= $index ?>" class="delete-button">❌ Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="color: white; text-align: center; width: 100%;">No tools found in this section</div>
        <?php endif; ?>
    </div>
</body>
</html>
