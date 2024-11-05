<?php
session_start();

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to index page
    exit();
}

// Ensure only admin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

include('db.php'); // Database connection

// Set session timeout duration (in seconds)
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: index.php?timeout=true');
    exit;
}
$_SESSION['last_activity'] = time(); // Update last activity timestamp

// Handle user role change
if (isset($_POST['change_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    // Ensure safe database execution
    if ($conn->query("UPDATE users SET role='$new_role' WHERE user_id=$user_id") === TRUE) {
        // Success (optional: set a success message)
    } else {
        // Handle error (optional: set an error message)
        error_log("Error changing user role: " . $conn->error);
    }
}

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    $conn->query("DELETE FROM users WHERE user_id=$user_id");
    header("Location: admin_dashboard.php"); // Redirect to admin dashboard
    exit;
}

// Handle book addition
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $available = isset($_POST['available']) ? 1 : 0; // Convert to boolean
    $conn->query("INSERT INTO books (title, author, available) VALUES ('$title', '$author', '$available')");
    header("Location: admin_dashboard.php"); // Redirect to admin dashboard
    exit;
}

// Handle book deletion
if (isset($_GET['delete_book'])) {
    $book_id = $_GET['delete_book'];
    $conn->query("DELETE FROM books WHERE book_id=$book_id");
    header("Location: admin_dashboard.php"); // Redirect to admin dashboard
    exit;
}

// Handle book editing
if (isset($_POST['edit_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $available = isset($_POST['available']) ? 1 : 0; // Convert to boolean
    $conn->query("UPDATE books SET title='$title', author='$author', available='$available' WHERE book_id=$book_id");
    header("Location: admin_dashboard.php"); // Redirect to admin dashboard
    exit;
}

// Handle force return of book
if (isset($_GET['force_return'])) {
    $rental_id = $_GET['force_return'];
    $return_date = date('Y-m-d H:i:s'); // Set current date and time as return date
    $conn->query("UPDATE rentals SET return_date='$return_date' WHERE rental_id=$rental_id");
    header("Location: admin_dashboard.php"); // Redirect to admin dashboard
    exit;
}

// Handle rental deletion
if (isset($_GET['remove_rental'])) {
    $rental_id = $_GET['remove_rental'];
    $conn->query("DELETE FROM rentals WHERE rental_id=$rental_id");
    header("Location: admin_dashboard.php"); // Redirect to admin dashboard
    exit;
}

// Handle suggestion deletion
if (isset($_GET['delete_suggestion'])) {
    $suggestion_id = $_GET['delete_suggestion'];
    $conn->query("DELETE FROM suggestion WHERE suggestion_id=$suggestion_id");
    header("Location: admin_dashboard.php");
    exit;
}


// Fetch all books for display
$books = $conn->query("SELECT * FROM books");

// Fetch all rentals for display
$rentals = $conn->query("SELECT r.rental_id, u.username, b.title, r.rental_date, r.return_date 
                          FROM rentals r 
                          JOIN users u ON r.user_id = u.user_id 
                          JOIN books b ON r.book_id = b.book_id");

// Fetch all suggestions for display
$suggestions = $conn->query("SELECT s.suggestion_id, u.username, s.suggest_book_name, s.author, s.suggestion_date
                             FROM suggestion s
                             JOIN users u ON s.user_id = u.user_id");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Confirmation dialog for deletion
        function confirmDelete(message, url) {
            if (confirm(message)) {
                window.location.href = url;
            }
        }

        // Function to populate the edit modal
        function populateEditModal(bookId, title, author, available) {
            $('#editBookId').val(bookId);
            $('#editBookTitle').val(title);
            $('#editBookAuthor').val(author);
            $('#editBookAvailable').prop('checked', available == 1);
            $('#editBookModal').modal('show');
        }
    </script>
    <style>
        /* Custom styles */
        body {
            background-color: #f5f5f5;
        }

        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }

        .tab-content h4 {
            font-weight: bold;
            color: #007bff;
            margin-top: 20px;
        }

        .table {
            background-color: #ffffff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-control,
        .btn-primary {
            border-radius: 20px;
        }

        .navbar-nav .nav-item .nav-link {
            font-weight: 500;
            color: #555;
        }

        .navbar-nav .nav-item .nav-link.active {
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2 class="mt-4 mb-3">Admin Dashboard</h2>

        <!-- Bootstrap Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item active">
                        <a class="nav-link" href="#manageUsers" data-toggle="tab"><i class="fas fa-users"></i> Manage
                            Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#manageBooks" data-toggle="tab"><i class="fas fa-book"></i> Manage
                            Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#viewRentals" data-toggle="tab"><i class="fas fa-receipt"></i> View
                            Rentals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#viewSuggestions" data-toggle="tab"><i
                                class="fas fa-lightbulb"></i>View
                            Suggestions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="tab-content">
            <!-- Manage Users Section -->
            <div class="tab-pane fade show active" id="manageUsers">
                <h4>Manage Users</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    $stmt = $conn->query("SELECT * FROM users");
                    while ($user = $stmt->fetch_assoc()) {
                        echo "<tr>
                        <td>{$user['username']}</td>
                        <td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='user_id' value='{$user['user_id']}'>
                                <select name='new_role' class='form-control' onchange='this.form.submit()'>
                                    <option value='student' " . ($user['role'] == 'student' ? 'selected' : '') . ">Student</option>
                                    <option value='teacher' " . ($user['role'] == 'teacher' ? 'selected' : '') . ">Teacher</option>
                                    <option value='admin' " . ($user['role'] == 'admin' ? 'selected' : '') . ">Admin</option>
                                </select>
                                <input type='hidden' name='change_role' value='1'>
                            </form>
                        </td>
                        <td>
                            <button class='btn btn-danger' onclick=\"confirmDelete('Are you sure you want to delete this user?', '?delete_user={$user['user_id']}')\">Delete</button>
                        </td>
                    </tr>";
                    }
                    ?>
                </table>
            </div>

            <!-- Manage Books Section -->
            <div class="tab-pane fade" id="manageBooks">
                <h4>Manage Books</h4>
                <form method="POST">
                    <div class="form-group">
                        <label for="title">Book Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" name="author" class="form-control" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="available" class="form-check-input" checked>
                        <label class="form-check-label">Available</label>
                    </div>
                    <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                </form>

                <table class="table table-bordered mt-2 ">
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Available</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    while ($book = $books->fetch_assoc()) {
                        echo "<tr>
                        <td>{$book['title']}</td>
                        <td>{$book['author']}</td>
                        <td>" . ($book['available'] ? 'Yes' : 'No') . "</td>
                        <td>
                            <button class='btn btn-warning' onclick=\"populateEditModal({$book['book_id']}, '{$book['title']}', '{$book['author']}', {$book['available']})\">Edit</button>
                            <button class='btn btn-danger' onclick=\"confirmDelete('Are you sure you want to delete this book?', '?delete_book={$book['book_id']}')\">Delete</button>
                        </td>
                    </tr>";
                    }
                    ?>
                </table>
            </div>


            <!-- View Suggestions Section -->
            <div class="tab-pane fade" id="viewSuggestions">
                <h4>View Suggestions</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>User</th>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Suggestion Date</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    while ($suggestion = $suggestions->fetch_assoc()) {
                        echo "<tr>
                            <td>{$suggestion['username']}</td>
                            <td>{$suggestion['suggest_book_name']}</td>
                            <td>{$suggestion['author']}</td>
                            <td>{$suggestion['suggestion_date']}</td>
                            <td>
                                <button class='btn btn-danger' onclick=\"confirmDelete('Are you sure you want to delete this suggestion?', '?delete_suggestion={$suggestion['suggestion_id']}')\">Delete</button>
                            </td>
                        </tr>";
                    }
                    ?>
                </table>
            </div>

            <!-- View Rentals Section -->
            <div class="tab-pane fade" id="viewRentals">
                <h4>View Rentals</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>User</th>
                        <th>Book</th>
                        <th>Rental Date</th>
                        <th>Return Date</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    while ($rental = $rentals->fetch_assoc()) {
                        echo "<tr>
                        <td>{$rental['username']}</td>
                        <td>{$rental['title']}</td>
                        <td>{$rental['rental_date']}</td>
                        <td>" . ($rental['return_date'] ? $rental['return_date'] : 'Not returned') . "</td>
                        <td>
                            <button class='btn btn-danger' onclick=\"confirmDelete('Are you sure you want to remove this rental?', '?remove_rental={$rental['rental_id']}')\">Remove</button>
                            <button class='btn btn-secondary' onclick=\"confirmDelete('Are you sure you want to force return this book?', '?force_return={$rental['rental_id']}')\">Force Return</button>
                        </td>
                    </tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1" role="dialog" aria-labelledby="editBookModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="book_id" id="editBookId">
                        <div class="form-group">
                            <label for="editBookTitle">Title</label>
                            <input type="text" class="form-control" id="editBookTitle" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="editBookAuthor">Author</label>
                            <input type="text" class="form-control" id="editBookAuthor" name="author" required>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="editBookAvailable" name="available">
                            <label class="form-check-label" for="editBookAvailable">Available</label>
                        </div>
                        <button type="submit" name="edit_book" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>