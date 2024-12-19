<html>
<head>
    <title>Database Messages</title>
    <style>
        table, th, td {
            border: 1px solid;
        }
    </style>
</head>
<body>
<h3>Database contains</h3>

<table style="border: 1px solid black">
    <thead>
    <tr><td>id</td><td>message</td></tr>
    </thead>
    <tbody>
    {messages}
    <tr><td style="text-align: right; padding-left: 10px;">{id}</td><td>{message}</td></tr>
    {/messages}
    </tbody>
</table>

</body>
</html>
