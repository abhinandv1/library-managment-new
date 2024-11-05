<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect to login if not logged in
    exit;
}

include('db.php'); // Database connection
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Get the username from session

// Set session timeout duration (in seconds)
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: index.php?timeout=true');
        exit;
    }
}
$_SESSION['last_activity'] = time(); // Update last activity timestamp

// Rent a book
if (isset($_POST['rent_book_id'])) {
    $book_id = $_POST['rent_book_id'];

    // Check if the book is available
    $checkBook = $conn->query("SELECT available FROM books WHERE book_id = $book_id")->fetch_assoc();
    if ($checkBook && $checkBook['available']) {
        // Rent the book
        $conn->query("INSERT INTO rentals (user_id, book_id, rental_date) VALUES ($user_id, $book_id, NOW())");
        $conn->query("UPDATE books SET available = 0 WHERE book_id = $book_id");
        echo "<script>alert('Book rented successfully!');</script>";
    } else {
        echo "<script>alert('This book is currently unavailable.');</script>";
    }
}

// Return a book
if (isset($_POST['return_book_id'])) {
    $book_id = $_POST['return_book_id'];

    // Update rental status and mark book as available
    $conn->query("UPDATE rentals SET return_date = NOW() WHERE book_id = $book_id AND user_id = $user_id AND return_date IS NULL");
    $conn->query("UPDATE books SET available = 1 WHERE book_id = $book_id");
    echo "<script>alert('Book returned successfully!');</script>";
}

// Book suggestion
if (isset($_POST['suggest_book_name']) && isset($_POST['author'])) {
    $suggest_book_name = $conn->real_escape_string($_POST['suggest_book_name']);
    $author = $conn->real_escape_string($_POST['author']);

    $conn->query("INSERT INTO suggestion (user_id, suggest_book_name, author) VALUES ($user_id, '$suggest_book_name', '$author')");
    echo "<script>alert('Book suggestion submitted successfully!');</script>";
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php"); // Redirect to index.php after logout
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #f5f5f5;
        }

        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }

        .table {
            background-color: #ffffff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="mt-4 mb-3"><?= htmlspecialchars($username) ?>'s Dashboard</h2>

        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">Dashboard</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item active">
                        <a class="nav-link" href="#listBooks" data-toggle="tab"><i class="fas fa-book"></i> List
                            Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#returnBook" data-toggle="tab"><i class="fas fa-undo-alt"></i> Return
                            Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#suggestBook" data-toggle="tab"><i class="fas fa-lightbulb"></i>
                            Suggest Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="tab-content">
            <!-- List Books Section -->
            <div class="tab-pane fade show active" id="listBooks">
                <h4>Available Books</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $books = $conn->query("SELECT * FROM books WHERE available = 1");
                    while ($book = $books->fetch_assoc()) {
                        echo "<tr>
                                <td>{$book['title']}</td>
                                <td>{$book['author']}</td>
                                <td>
                                    <form method='POST'>
                                        <input type='hidden' name='rent_book_id' value='{$book['book_id']}'>
                                        <button type='submit' class='btn btn-primary'>Rent Book</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                    ?>
                </table>
            </div>

            <!-- Return Book Section -->
            <div class="tab-pane fade" id="returnBook">
                <h4>Return Rented Books</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Rental Date</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $rentedBooks = $conn->query("SELECT books.title, books.author, rentals.rental_date, rentals.book_id 
                                                 FROM rentals 
                                                 JOIN books ON rentals.book_id = books.book_id 
                                                 WHERE rentals.user_id = $user_id AND rentals.return_date IS NULL");
                    while ($rented = $rentedBooks->fetch_assoc()) {
                        echo "<tr>
                                <td>{$rented['title']}</td>
                                <td>{$rented['author']}</td>
                                <td>{$rented['rental_date']}</td>
                                <td>
                                    <form method='POST'>
                                        <input type='hidden' name='return_book_id' value='{$rented['book_id']}'>
                                        <button type='submit' class='btn btn-warning'>Return Book</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                    ?>
                </table>
            </div>

            <!-- Suggest Book Section -->
            <div class="tab-pane fade" id="suggestBook">
                <h4>Suggest a New Book</h4>
                <form method="POST">
                    <div class="form-group">
                        <label for="suggest_book_name">Book Name</label>
                        <input type="text" name="suggest_book_name" class="form-control" id="suggest_book_name"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" name="author" class="form-control" id="author" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Suggestion</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>