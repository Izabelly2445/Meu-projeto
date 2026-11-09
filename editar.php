<?php
require __DIR__ . '/cliente.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$erro = null;
$t = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        $erro = 'Identificador inválido.';
        $t = $_POST;
    } else {
        $r = chamar_api('PUT', $id, [
            'aluno' => $_POST['aluno'] ?? '',
            'turma' => $_POST['turma'] ?? '',
            'disciplina' => $_POST['disciplina'] ?? '',
            'bimestre' => $_POST['bimestre'] ?? '',
            'nota' => $_POST['nota'] ?? '',
        ]);

        if ($r['status'] === 200) {
            header('Location: index.php');
            exit;
        }

        $erro = $r['dados']['erro'] ?? 'Erro ao atualizar a nota.';
        $t = $_POST;
    }
} elseif ($id && $id > 0) {
    $r = chamar_api('GET', $id);

    if ($r['status'] === 200) {
        $t = $r['dados'];
    } else {
        $erro = $r['dados']['erro'] ?? 'Nota não encontrada.';
    }
} else {
    $erro = 'Identificador inválido.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar nota</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <section class="painel">
        <h1>Editar nota</h1>

        <?php if ($erro): ?>
            <p class="erro"><?= h($erro) ?></p>
        <?php endif; ?>

        <?php if ($t): ?>
        <form method="post" class="formulario">
            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <label>Aluno
                <input name="aluno" maxlength="120"
                       value="<?= h($t['aluno'] ?? '') ?>" required>
            </label>

            <label>Turma
                <input name="turma" maxlength="30"
                       value="<?= h($t['turma'] ?? '') ?>" required>
            </label>

            <label>Disciplina
                <input name="disciplina" maxlength="100"
                       value="<?= h($t['disciplina'] ?? '') ?>" required>
            </label>

            <label>Bimestre
                <select name="bimestre" required>
                    <?php for ($b = 1; $b <= 4; $b++): ?>
                    <option value="<?= $b ?>"
                        <?= (int)$t['bimestre'] === $b ? 'selected' : '' ?>>
                        <?= $b ?>º bimestre
                    </option>
                    <?php endfor; ?>
                </select>
            </label>

            <label>Nota
                <input type="number" name="nota" min="0" max="10"
                       step="0.01" value="<?= h((string)$t['nota']) ?>"
                       required>
            </label>

            <div class="acoes">
                <button type="submit">Salvar alterações</button>
                <a class="btn voltar" href="index.php">Voltar</a>
            </div>
        </form>
        <?php else: ?>
            <a class="btn voltar" href="index.php">Voltar</a>
        <?php endif; ?>
    </section>
</main>
</body>
</html>