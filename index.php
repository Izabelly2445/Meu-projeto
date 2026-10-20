<?php
require __DIR__ . '/cliente.php';

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $r = chamar_api('POST', null, [
            'aluno' => $_POST['aluno'] ?? '',
            'turma' => $_POST['turma'] ?? '',
            'disciplina' => $_POST['disciplina'] ?? '',
            'bimestre' => $_POST['bimestre'] ?? '',
            'nota' => $_POST['nota'] ?? '',
        ]);
    } elseif ($acao === 'excluir') {
        $r = chamar_api('DELETE', (int)($_POST['id'] ?? 0));
    } else {
        $r = ['status' => 400, 'dados' => ['erro' => 'Ação inválida.']];
    }

    if ($r['status'] >= 200 && $r['status'] < 300) {
        header('Location: index.php');
        exit;
    }

    $erro = $r['dados']['erro'] ?? 'Não foi possível executar a ação.';
}

$r = chamar_api('GET');
$registros = $r['status'] === 200 ? $r['dados'] : [];

if ($r['status'] !== 200) {
    $erro = $r['dados']['erro'] ?? 'Falha ao carregar o boletim.';
}

$alunos = [];
$somas = [];

foreach ($registros as $t) {
    $chave = $t['aluno'] . '|' . $t['turma'];

    if (!isset($alunos[$chave])) {
        $alunos[$chave] = [
            'nome' => $t['aluno'],
            'turma' => $t['turma'],
            'disciplinas' => [],
        ];
    }

    $disciplina = $t['disciplina'];

    if (!isset($alunos[$chave]['disciplinas'][$disciplina])) {
        $alunos[$chave]['disciplinas'][$disciplina] = [
            'soma' => 0,
            'quantidade' => 0,
        ];
    }

    $alunos[$chave]['disciplinas'][$disciplina]['soma'] += (float)$t['nota'];
    $alunos[$chave]['disciplinas'][$disciplina]['quantidade']++;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boletim Escolar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topo">
    <div>
        <span class="marca">EDUCA+</span>
        <h1>Boletim Escolar</h1>
        <p>Notas, médias e desempenho acadêmico</p>
    </div>
    <span class="etiqueta">Área escolar</span>
</header>

<main class="container">
    <section class="painel">
        <h2>Cadastrar nota</h2>

        <form method="post" class="formulario">
            <input type="hidden" name="acao" value="criar">

            <label>Nome do aluno
                <input name="aluno" maxlength="120" required>
            </label>

            <label>Turma
                <input name="turma" maxlength="30"
                       placeholder="Ex.: 9º A" required>
            </label>

            <label>Disciplina
                <input name="disciplina" maxlength="100" required>
            </label>

            <label>Bimestre
                <select name="bimestre" required>
                    <option value="">Selecione</option>
                    <option value="1">1º bimestre</option>
                    <option value="2">2º bimestre</option>
                    <option value="3">3º bimestre</option>
                    <option value="4">4º bimestre</option>
                </select>
            </label>

            <label>Nota (0 a 10)
                <input type="number" name="nota"
                       min="0" max="10" step="0.01" required>
            </label>

            <button type="submit">Salvar nota</button>
        </form>

        <?php if ($erro): ?>
            <p class="erro"><?= h($erro) ?></p>
        <?php endif; ?>
    </section>

    <section class="painel">
        <div class="cabecalho-lista">
            <div>
                <h2>Boletins cadastrados</h2>
                <p>Consulte as notas e médias por disciplina.</p>
            </div>
            <input type="search" id="pesquisa"
                   placeholder="Pesquisar aluno...">
        </div>

        <?php if (!$registros): ?>
            <p class="vazio">Nenhuma nota cadastrada.</p>
        <?php else: ?>
        <div class="tabela-wrap">
            <table id="tabela">
                <thead>
                    <tr>
                        <th>Aluno / turma</th>
                        <th>Disciplina</th>
                        <th>Bimestre</th>
                        <th>Nota</th>
                        <th>Média</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($registros as $t):
                    $media = (float)$t['media'];
                    $quantidade = (int)$t['bimestres'];

                    $situacao = $quantidade < 4
                        ? 'Em andamento'
                        : ($media >= 7 ? 'Aprovado' : 'Recuperação');

                    $classe = $situacao === 'Aprovado'
                        ? 'aprovado'
                        : ($situacao === 'Recuperação'
                            ? 'recuperacao' : 'andamento');
                ?>
                    <tr>
                        <td>
                            <strong><?= h($t['aluno']) ?></strong>
                            <small><?= h($t['turma']) ?></small>
                        </td>
                        <td><?= h($t['disciplina']) ?></td>
                        <td><?= (int)$t['bimestre'] ?>º</td>
                        <td><?= number_format((float)$t['nota'], 2, ',', '.') ?></td>
                        <td><?= number_format($media, 2, ',', '.') ?></td>
                        <td><span class="status <?= $classe ?>">
                            <?= h($situacao) ?>
                        </span></td>
                        <td class="acoes">
                            <a class="btn editar"
                               href="editar.php?id=<?= (int)$t['id'] ?>">Editar</a>

                            <form method="post"
                                  onsubmit="return confirm('Excluir esta nota?')">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id"
                                       value="<?= (int)$t['id'] ?>">
                                <button class="btn excluir"
                                        type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</main>

<script>
document.querySelector('#pesquisa').addEventListener('input', function () {
    const termo = this.value.toLocaleLowerCase('pt-BR');

    document.querySelectorAll('#tabela tbody tr').forEach(linha => {
        linha.hidden = !linha.textContent.toLocaleLowerCase('pt-BR')
            .includes(termo);
    });
});
</script>
</body>
</html>