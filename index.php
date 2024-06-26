<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "taskdb";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Sorry we failed to connect" . mysqli_connect_error());
} else {
    // echo "Connection was successful!<br/>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        //transaction begins
        $conn->begin_transaction();

        //storing values coming through Form
        $qty = $_POST['quantity'];
        $price = $_POST['price'];

        //Getting row with the matching price
        $sql = "SELECT * FROM `pending_order` WHERE `seller_price` = $price";
        $result = $conn->query($sql);

        $row = $result->fetch_assoc();

        if ($row != null) {

            //Working on the query on the basis of different conditions
            if ($row['seller_qty'] == $qty) {
                //updating pending_order table
                $sql_remove_value = "UPDATE pending_order SET seller_qty = 0  WHERE seller_price = $price";
                $conn->query($sql_remove_value);

                //updating completed_order table
                $insert_order = "INSERT INTO `completed_order`(`price`, `qty`) VALUES ($price, $qty)";
                $insert_result = $conn->query($insert_order);
                echo "<script type='text/javascript'>alert('Order Completed!');</script>";
            } else if ($row['seller_qty'] > $qty) {
                //updating pending_order table
                $update_sql = "UPDATE pending_order SET seller_qty = seller_qty - $qty  WHERE seller_price = $price";
                $update_result = $conn->query($update_sql);

                //updating completed_order table
                $insert_order = "INSERT INTO `completed_order`(`price`, `qty`) VALUES ($price, $qty)";
                $insert_result = $conn->query($insert_order);
                echo "<script type='text/javascript'>alert('Order Completed!');</script>";
            } else {
                $qty = $qty - $row['seller_qty'];
                //updating pending_order table
                $update_sql = "UPDATE pending_order SET seller_qty = 0  WHERE seller_price = $price";
                $update_result = $conn->query($update_sql);

                //inserting remaining quantity in pending_order table
                $insert_pending_order = "INSERT INTO `pending_order`(`buy_price`, `buy_qty`) VALUES ($price, $qty)";
                $insert_result = $conn->query($insert_pending_order);


                //updating completed_order table
                $insert_completed_order = "INSERT INTO `completed_order`(`price`, `qty`) VALUES ($price, $qty)";
                $insert_result = $conn->query($insert_completed_order);

                //showing the alert according to quantity remaining
                if ($qty == 0) {
                    echo "<script type='text/javascript'>alert('Out of stock! Order in Pending State!');</script>";
                } else {
                    echo "<script type='text/javascript'>alert('$qty order places, rest in pending state due to OUT of Stock');</script>";
                }
            }
        } else {
            $insert_pending_order = "INSERT INTO `pending_order`(`buy_price`, `buy_qty`) VALUES ($price, $qty)";
            $insert_result = $conn->query($insert_pending_order);
            echo "<script type='text/javascript'>alert('No Stock available at this price at present, Order in pending orders.');</script>";
        }
        //commiting transaction
        $conn->commit();
        echo "<script type='text/javascript'>window.location.href = 'index.php';</script>";
    } catch (Exception $e) {
        // Roll back transaction in case of error
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Matching System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">


    <style>
        .form-container {
            padding: 40px;
            margin-top: 100px;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
            border: black solid 2px;
        }

        .table-container {
            padding: 20px;
            display: flex;
            justify-content: space-evenly;
            gap: 150px;
        }
    </style>

</head>

<body>
    <div class="container form-container">
        <center>
            <h1>Order Matching System</h1>
        </center>
        <form action="index.php" method="post">
            <div class="mb-3">
                <label for="exampleInputPrice" class="form-label">Price</label>
                <input type="number" name="price" class="form-control" min="0" id="exampleInputPrice">
            </div>
            <div class="mb-3">
                <label for="exampleInputQuantity" class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" min="0" id="exampleInputQuantity">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <div class="table-container container">
            <div class="table-item">
                <h3>Pending Order Data</h3>
                <table class="table table-striped-columns">
                    <thead>
                        <tr>
                            <th scope="col">Buy Quantity</th>
                            <th scope="col">Buy Price</th>
                            <th scope="col">Seller Price</th>
                            <th scope="col">Seller Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_buy = 'SELECT * FROM `pending_order`';
                        $result_buy = $conn->query($sql_buy);

                        $sql_seller = 'SELECT * FROM `pending_order` WHERE seller_qty !=0';
                        $result_seller = $conn->query($sql_seller);

                        while ($row_buy = $result_buy->fetch_assoc()) {
                            $row_seller = $result_seller->fetch_assoc();
                            if ($row_seller != null) {
                                $seller_price = $row_seller['seller_price'];
                                $seller_qunantity = $row_seller['seller_qty'];
                            } else {
                                $seller_price = NULL;
                                $seller_qunantity = NULL;
                            }
                            echo "<tr>
                        <td>" . $row_buy['buy_qty'] . "</td>
                        <td>" . $row_buy['buy_price'] . "</td>
                        <td>" . $seller_price . "</td>
                        <td>" . $seller_qunantity . "</td>
                    </tr>
                        ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="table-item">
                <h3>Completed Order Data</h3>
                <table class="table table-striped-columns">
                    <thead>
                        <tr>
                            <th scope="col">Price</th>
                            <th scope="col">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_completed = 'SELECT * FROM `completed_order`';
                        $result_comp = $conn->query($sql_completed);

                        while ($row_comp = $result_comp->fetch_assoc()) {
                            echo "<tr>
                            <td>" . $row_comp['price'] . "</td>
                            <td>" . $row_comp['qty'] . "</td>
                        </tr>
                        ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>