<html>

<head>

    <style>
        /* various styling to make the table look better  */

        body
        {
            font-family: sans-serif;
        }
        .report_table, .report_table tr td
        {
            border: 1px solid #999;
            border-collapse: collapse;
        }

        .report_table tr:nth-child(2n)
        {
            background-color: #f9f9f9;
        }
        .report_table tr:nth-child(2n+1)
        {
            background-color: #DDE6E7;
        }

        .report_table tr th
        {
            border: 1px solid #999;
            background-color: #A7A7A7;
            padding: 5px 10px 5px 10px;
        }

        .report_table tr td
        {
            padding: 5px;
        }

    </style>

</head>

<body>

<h3><?php echo $description; ?></h3>

<table class="report_table">
    <tr>
        <th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Author</th>
    </tr>
    <?php foreach($books as $book): ?>
    <tr>
        <td><?php echo $book['id']; ?></td>
        <td><?php echo $book['name']; ?></td>
        <td><?php echo $book['description']; ?></td>
        <td><?php echo $book['price']; ?></td>
        <td><?php echo $book['Author']['name']; ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>

</html>