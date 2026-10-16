<?php
/* Основные настройки */
define('DB_HOST', 'MySQL-8.4');
define('DB_LOGIN', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gbook');

$db = mysqli_connect(DB_HOST, DB_LOGIN, DB_PASSWORD);
mysqli_query($db, 'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4');
mysqli_select_db($db, DB_NAME);
mysqli_set_charset($db, 'utf8mb4');
mysqli_query($db, "CREATE TABLE IF NOT EXISTS msgs (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(25) NOT NULL DEFAULT '',
    email varchar(50) NOT NULL DEFAULT '',
    msg text,
    datetime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* Сохранение записи в БД */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['email'], $_POST['msg'])) {
    $name = trim(strip_tags($_POST['name']));
    $email = trim(strip_tags($_POST['email']));
    $msg = trim(strip_tags($_POST['msg']));

    if ($name !== '' && $email !== '' && $msg !== '') {
        $stmt = mysqli_prepare($db, 'INSERT INTO msgs (name, email, msg) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $msg);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            echo '<p>Запись успешно добавлена.</p>';
        } else {
            echo '<p>Ошибка при добавлении записи: ' . htmlspecialchars(mysqli_stmt_error($stmt)) . '</p>';
        }
        mysqli_stmt_close($stmt);
    } else {
        echo '<p>Заполните все поля формы.</p>';
    }
}

/* Удаление записи из БД */
if (isset($_GET['del'])) {
    $del = (int) $_GET['del'];

    if ($del > 0) {
        $stmt = mysqli_prepare($db, 'DELETE FROM msgs WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $del);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            echo '<p>Запись удалена.</p>';
        } else {
            echo '<p>Ошибка при удалении записи: ' . htmlspecialchars(mysqli_stmt_error($stmt)) . '</p>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<h3>Оставьте запись в нашей Гостевой книге</h3>

<form method="post" action="<?= $_SERVER['REQUEST_URI'] ?>">
Имя: <br /><input type="text" name="name" /><br />
Email: <br /><input type="text" name="email" /><br />
Сообщение: <br /><textarea name="msg"></textarea><br />

<br />

<input type="submit" value="Отправить!" />

</form>
<?php
/* Вывод записей из БД sql injection */
$sql = "SELECT id, name, email, msg, UNIX_TIMESTAMP(datetime) AS dt FROM msgs ORDER BY id DESC";
$stmt = mysqli_prepare($db, $sql);

if (!$stmt || !mysqli_stmt_execute($stmt)) {
    $error = $stmt ? mysqli_stmt_error($stmt) : mysqli_error($db);
    echo '<p>Ошибка при выборке данных: ' . htmlspecialchars($error) . '</p>';
} else {
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_num_rows($result);
    echo '<p>Всего записей в гостевой книге: ' . $count . '</p>';

    while ($row = mysqli_fetch_assoc($result)) {
        $dt = date('d-m-Y в H:i', $row['dt']);
        echo '<p>';
        echo '<a href="mailto:' . htmlspecialchars($row['email']) . '">' . htmlspecialchars($row['name']) . '</a> ' . $dt . ' написал<br />';
        echo nl2br(htmlspecialchars($row['msg']));
        echo '</p>';
        echo '<p align="right"><a href="index.php?id=gbook&del=' . $row['id'] . '">Удалить</a></p>';
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($db);
?>