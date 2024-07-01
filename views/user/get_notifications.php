<?php
include('../../config/dbconn.php');

$sql = "SELECT ID, document, NameRecipient FROM indocument ORDER BY ID DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<li class="list-group-item list-group-item-action dropdown-notifications-item">';
        echo '  <div class="d-flex">';
        echo '    <div class="flex-shrink-0 me-3">';
        echo '      <p><strong>Document:</strong> ' . htmlspecialchars($row["document"]) . '</p>';
        echo '      <p><strong>Recipient:</strong> ' . htmlspecialchars($row["NameRecipient"]) . '</p>';
        echo '      <small class="text-muted"><strong>ID:</strong> ' . htmlspecialchars($row["ID"]) . '</small>';
        echo '    </div>';
        echo '  </div>';
        echo '</li>';
    }
} else {
    echo '<li class="list-group-item list-group-item-action dropdown-notifications-item">';
    echo '  <div class="d-flex">';
    echo '    <div class="flex-shrink-0 me-3">';
    echo '      <p>No documents</p>';
    echo '    </div>';
    echo '  </div>';
    echo '</li>';
}

$conn->close();
?>
