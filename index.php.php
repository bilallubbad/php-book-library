<?php
session_start();

/*
    Book Library Assignment
    This page allows the user to add, edit, delete, search, and sort books.
*/

// Function to safely print data on the page
function cleanText($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Allowed genres list
$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

// Create books array in session first time only
if (!isset($_SESSION["books"])) {
    $_SESSION["books"] = [
        [
            "id" => 1,
            "title" => "The Power of Habit",
            "author" => "Charles Duhigg",
            "genre" => "Non-Fiction",
            "year" => 2012,
            "pages" => 371
        ],
        [
            "id" => 2,
            "title" => "Steve Jobs",
            "author" => "Walter Isaacson",
            "genre" => "Biography",
            "year" => 2011,
            "pages" => 656
        ],
        [
            "id" => 3,
            "title" => "Learning PHP",
            "author" => "David Sklar",
            "genre" => "Technology",
            "year" => 2016,
            "pages" => 412
        ]
    ];
}

$books = $_SESSION["books"];
$errors = [];

$submittedData = [
    "id" => "",
    "title" => "",
    "author" => "",
    "genre" => "",
    "year" => "",
    "pages" => ""
];

$isEditMode = false;
$editId = null;
$buttonText = "Add Book";

// Get new id using maximum id + 1
function getNextBookId($books)
{
    $largestId = 0;

    foreach ($books as $book) {
        if ($book["id"] > $largestId) {
            $largestId = $book["id"];
        }
    }

    return $largestId + 1;
}

// Find book index by id
function getBookIndex($books, $id)
{
    foreach ($books as $index => $book) {
        if ((int)$book["id"] === (int)$id) {
            return $index;
        }
    }

    return -1;
}

// Check edit mode
if (isset($_GET["edit_id"])) {
    $editId = (int)$_GET["edit_id"];
    $bookIndex = getBookIndex($books, $editId);

    if ($bookIndex !== -1) {
        $isEditMode = true;
        $buttonText = "Update Book";
        $submittedData = $books[$bookIndex];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = isset($_POST["action"]) ? trim($_POST["action"]) : "";

    // Delete book
    if ($action === "delete") {
        $deleteId = isset($_POST["book_id"]) ? (int)$_POST["book_id"] : 0;

        $books = array_filter($books, function ($book) use ($deleteId) {
            return (int)$book["id"] !== $deleteId;
        });

        $books = array_values($books);

        $_SESSION["books"] = $books;
        $_SESSION["success"] = "Book deleted successfully.";

        header("Location: index.php");
        exit;
    }

    // Add or update book
    if ($action === "add" || $action === "update") {
        $submittedData = [
            "id" => isset($_POST["id"]) ? (int)$_POST["id"] : "",
            "title" => isset($_POST["title"]) ? htmlspecialchars(trim($_POST["title"]), ENT_QUOTES, "UTF-8") : "",
            "author" => isset($_POST["author"]) ? htmlspecialchars(trim($_POST["author"]), ENT_QUOTES, "UTF-8") : "",
            "genre" => isset($_POST["genre"]) ? htmlspecialchars(trim($_POST["genre"]), ENT_QUOTES, "UTF-8") : "",
            "year" => isset($_POST["year"]) ? htmlspecialchars(trim($_POST["year"]), ENT_QUOTES, "UTF-8") : "",
            "pages" => isset($_POST["pages"]) ? htmlspecialchars(trim($_POST["pages"]), ENT_QUOTES, "UTF-8") : ""
        ];

        // Validate title
        if ($submittedData["title"] === "") {
            $errors["title"] = "Title is required.";
        } elseif (strlen($submittedData["title"]) < 3 || strlen($submittedData["title"]) > 120) {
            $errors["title"] = "Title must be between 3 and 120 characters.";
        }

        // Validate author
        if ($submittedData["author"] === "") {
            $errors["author"] = "Author is required.";
        } else {
            $authorWords = preg_split("/\s+/", $submittedData["author"]);
            if (count($authorWords) < 2) {
                $errors["author"] = "Author must contain at least two words.";
            }
        }

        // Validate genre
        if ($submittedData["genre"] === "") {
            $errors["genre"] = "Genre is required.";
        } elseif (!in_array($submittedData["genre"], $genres)) {
            $errors["genre"] = "Please choose a valid genre.";
        }

        // Validate year
        $currentYear = (int)date("Y");

        if ($submittedData["year"] === "") {
            $errors["year"] = "Year is required.";
        } elseif (!preg_match("/^\d{4}$/", $submittedData["year"])) {
            $errors["year"] = "Year must be a 4-digit number.";
        } elseif ((int)$submittedData["year"] < 1000 || (int)$submittedData["year"] > $currentYear) {
            $errors["year"] = "Year must be between 1000 and " . $currentYear . ".";
        }

        // Validate pages
        if ($submittedData["pages"] === "") {
            $errors["pages"] = "Pages are required.";
        } elseif (!filter_var($submittedData["pages"], FILTER_VALIDATE_INT) || (int)$submittedData["pages"] <= 0) {
            $errors["pages"] = "Pages must be a positive integer greater than 0.";
        }

        // Save if no errors
        if (empty($errors)) {
            if ($action === "add") {
                $newBook = [
                    "id" => getNextBookId($books),
                    "title" => $submittedData["title"],
                    "author" => $submittedData["author"],
                    "genre" => $submittedData["genre"],
                    "year" => (int)$submittedData["year"],
                    "pages" => (int)$submittedData["pages"]
                ];

                $books[] = $newBook;

                $_SESSION["books"] = $books;
                $_SESSION["success"] = "Book added successfully.";

                header("Location: index.php");
                exit;
            }

            if ($action === "update") {
                $updateId = (int)$submittedData["id"];
                $bookIndex = getBookIndex($books, $updateId);

                if ($bookIndex !== -1) {
                    $books[$bookIndex] = [
                        "id" => $updateId,
                        "title" => $submittedData["title"],
                        "author" => $submittedData["author"],
                        "genre" => $submittedData["genre"],
                        "year" => (int)$submittedData["year"],
                        "pages" => (int)$submittedData["pages"]
                    ];

                    $_SESSION["books"] = $books;
                    $_SESSION["success"] = "Book updated successfully.";

                    header("Location: index.php");
                    exit;
                } else {
                    $errors["general"] = "Book was not found.";
                }
            }
        }

        if ($action === "update") {
            $isEditMode = true;
            $buttonText = "Update Book";
        }
    }
}

// Show and remove success message
$successMessage = "";

if (isset($_SESSION["success"])) {
    $successMessage = $_SESSION["success"];
    unset($_SESSION["success"]);
}

// Search books
$searchTerm = isset($_GET["search"]) ? htmlspecialchars(trim($_GET["search"]), ENT_QUOTES, "UTF-8") : "";
$displayBooks = $books;

if ($searchTerm !== "") {
    $matchedBooks = [];

    foreach ($books as $book) {
        if (
            stripos($book["title"], $searchTerm) !== false ||
            stripos($book["author"], $searchTerm) !== false
        ) {
            $matchedBooks[] = $book;
        }
    }

    $displayBooks = $matchedBooks;
}

// Sort books
$allowedSortColumns = ["id", "title", "author", "genre", "year", "pages"];
$sortColumn = isset($_GET["sort"]) ? $_GET["sort"] : "";
$sortDirection = isset($_GET["direction"]) ? $_GET["direction"] : "asc";

if (in_array($sortColumn, $allowedSortColumns)) {
    usort($displayBooks, function ($a, $b) use ($sortColumn, $sortDirection) {
        if ($a[$sortColumn] == $b[$sortColumn]) {
            return 0;
        }

        if ($sortDirection === "desc") {
            return ($a[$sortColumn] < $b[$sortColumn]) ? 1 : -1;
        }

        return ($a[$sortColumn] > $b[$sortColumn]) ? 1 : -1;
    });
}

// Create sorting link
function makeSortLink($column, $label, $currentSort, $currentDirection, $searchTerm)
{
    $newDirection = "asc";

    if ($currentSort === $column && $currentDirection === "asc") {
        $newDirection = "desc";
    }

    $url = "index.php?sort=" . urlencode($column) . "&direction=" . urlencode($newDirection);

    if ($searchTerm !== "") {
        $url .= "&search=" . urlencode($searchTerm);
    }

    return '<a href="' . $url . '" class="text-white text-decoration-none">' . cleanText($label) . '</a>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Library</title>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container my-5">

    <div class="text-center mb-4">
        <h1 class="fw-bold">Personal Book Library</h1>
        <p class="text-muted">A simple PHP application for managing books.</p>
    </div>

    <?php if ($successMessage !== ""): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo cleanText($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Form column -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <?php echo $isEditMode ? "Edit Book" : "Add New Book"; ?>
                </div>

                <div class="card-body">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            Please check the form errors below.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php<?php echo $isEditMode ? '?edit_id=' . cleanText($submittedData["id"]) : ''; ?>">

                        <input type="hidden" name="action" value="<?php echo $isEditMode ? 'update' : 'add'; ?>">
                        <input type="hidden" name="id" value="<?php echo cleanText($submittedData["id"]); ?>">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input
                                type="text"
                                name="title"
                                id="title"
                                class="form-control <?php echo isset($errors["title"]) ? 'is-invalid' : ''; ?>"
                                value="<?php echo cleanText($submittedData["title"]); ?>"
                            >
                            <?php if (isset($errors["title"])): ?>
                                <div class="invalid-feedback">
                                    <?php echo cleanText($errors["title"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author</label>
                            <input
                                type="text"
                                name="author"
                                id="author"
                                class="form-control <?php echo isset($errors["author"]) ? 'is-invalid' : ''; ?>"
                                value="<?php echo cleanText($submittedData["author"]); ?>"
                            >
                            <?php if (isset($errors["author"])): ?>
                                <div class="invalid-feedback">
                                    <?php echo cleanText($errors["author"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="genre" class="form-label">Genre</label>
                            <select
                                name="genre"
                                id="genre"
                                class="form-select <?php echo isset($errors["genre"]) ? 'is-invalid' : ''; ?>"
                            >
                                <option value="">Select genre</option>

                                <?php foreach ($genres as $genre): ?>
                                    <option
                                        value="<?php echo cleanText($genre); ?>"
                                        <?php echo ($submittedData["genre"] === $genre) ? "selected" : ""; ?>
                                    >
                                        <?php echo cleanText($genre); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if (isset($errors["genre"])): ?>
                                <div class="invalid-feedback">
                                    <?php echo cleanText($errors["genre"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input
                                type="text"
                                name="year"
                                id="year"
                                class="form-control <?php echo isset($errors["year"]) ? 'is-invalid' : ''; ?>"
                                value="<?php echo cleanText($submittedData["year"]); ?>"
                            >
                            <?php if (isset($errors["year"])): ?>
                                <div class="invalid-feedback">
                                    <?php echo cleanText($errors["year"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="pages" class="form-label">Pages</label>
                            <input
                                type="text"
                                name="pages"
                                id="pages"
                                class="form-control <?php echo isset($errors["pages"]) ? 'is-invalid' : ''; ?>"
                                value="<?php echo cleanText($submittedData["pages"]); ?>"
                            >
                            <?php if (isset($errors["pages"])): ?>
                                <div class="invalid-feedback">
                                    <?php echo cleanText($errors["pages"]); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <?php echo cleanText($buttonText); ?>
                        </button>

                        <?php if ($isEditMode): ?>
                            <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        </div>

        <!-- Table column -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    Books List
                </div>

                <div class="card-body">

                    <form method="GET" action="index.php" class="mb-3">
                        <div class="input-group">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search by title or author"
                                value="<?php echo cleanText($searchTerm); ?>"
                            >
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th><?php echo makeSortLink("id", "#", $sortColumn, $sortDirection, $searchTerm); ?></th>
                                    <th><?php echo makeSortLink("title", "Title", $sortColumn, $sortDirection, $searchTerm); ?></th>
                                    <th><?php echo makeSortLink("author", "Author", $sortColumn, $sortDirection, $searchTerm); ?></th>
                                    <th><?php echo makeSortLink("genre", "Genre", $sortColumn, $sortDirection, $searchTerm); ?></th>
                                    <th><?php echo makeSortLink("year", "Year", $sortColumn, $sortDirection, $searchTerm); ?></th>
                                    <th><?php echo makeSortLink("pages", "Pages", $sortColumn, $sortDirection, $searchTerm); ?></th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($displayBooks)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No books found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($displayBooks as $book): ?>
                                        <tr>
                                            <td><?php echo cleanText($book["id"]); ?></td>
                                            <td><?php echo cleanText($book["title"]); ?></td>
                                            <td><?php echo cleanText($book["author"]); ?></td>
                                            <td><?php echo cleanText($book["genre"]); ?></td>
                                            <td><?php echo cleanText($book["year"]); ?></td>
                                            <td><?php echo cleanText($book["pages"]); ?></td>
                                            <td>
                                                <a
                                                    href="index.php?edit_id=<?php echo cleanText($book["id"]); ?>"
                                                    class="btn btn-sm btn-warning mb-1"
                                                >
                                                    Edit
                                                </a>

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-danger mb-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal<?php echo cleanText($book["id"]); ?>"
                                                >
                                                    Delete
                                                </button>

                                                <!-- Delete modal -->
                                                <div
                                                    class="modal fade"
                                                    id="deleteModal<?php echo cleanText($book["id"]); ?>"
                                                    tabindex="-1"
                                                    aria-hidden="true"
                                                >
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">

                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">Confirm Delete</h5>
                                                                <button
                                                                    type="button"
                                                                    class="btn-close btn-close-white"
                                                                    data-bs-dismiss="modal"
                                                                    aria-label="Close"
                                                                ></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                Are you sure you want to delete
                                                                <strong><?php echo cleanText($book["title"]); ?></strong>?
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-secondary"
                                                                    data-bs-dismiss="modal"
                                                                >
                                                                    Cancel
                                                                </button>

                                                                <form method="POST" action="index.php">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input
                                                                        type="hidden"
                                                                        name="book_id"
                                                                        value="<?php echo cleanText($book["id"]); ?>"
                                                                    >
                                                                    <button type="submit" class="btn btn-danger">
                                                                        Yes, Delete
                                                                    </button>
                                                                </form>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small mb-0">
                        You can search books or click table headers to sort the list.
                    </p>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>