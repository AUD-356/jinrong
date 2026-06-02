<?php
function dbGetOne($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function dbGetRow($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function dbGetAll($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dbExecute($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function dbInsert($table, $data) {
    $fields = array_keys($data);
    $values = array_values($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = getDB()->prepare($sql);
    $stmt->execute($values);
    return getDB()->lastInsertId();
}

function dbUpdate($table, $data, $where, $whereParams = []) {
    $set = [];
    foreach (array_keys($data) as $field) {
        $set[] = "{$field} = ?";
    }
    $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE " . $where;
    $stmt = getDB()->prepare($sql);
    $stmt->execute(array_merge(array_values($data), $whereParams));
    return $stmt->rowCount();
}

function dbDelete($table, $where, $params = []) {
    $sql = "DELETE FROM {$table} WHERE " . $where;
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function dbColumnExists($table, $column) {
    $stmt = getDB()->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

function dbAddColumnText($table, $column) {
    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` TEXT";
    return getDB()->exec($sql);
}

function dbBeginTransaction() {
    return getDB()->beginTransaction();
}

function dbCommit() {
    return getDB()->commit();
}

function dbRollback() {
    return getDB()->rollBack();
}

function dbFetchColumn($sql, $params = []) {
    return dbGetOne($sql, $params);
}

function dbCount($table, $where = '1=1', $params = []) {
    return (int)dbGetOne("SELECT COUNT(*) FROM {$table} WHERE {$where}", $params);
}

function dbExists($table, $where, $params = []) {
    return dbGetOne("SELECT 1 FROM {$table} WHERE {$where} LIMIT 1", $params) !== false;
}

function dbLastInsertId() {
    return getDB()->lastInsertId();
}
?>