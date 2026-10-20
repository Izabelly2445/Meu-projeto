<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function responder(mixed $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function validar(array $d): array
{
    $aluno = trim($d['aluno'] ?? '');
    $turma = trim($d['turma'] ?? '');
    $disciplina = trim($d['disciplina'] ?? '');
    $bimestre = filter_var($d['bimestre'] ?? null, FILTER_VALIDATE_INT);
    $nota = $d['nota'] ?? null;

    if ($aluno === '' || $turma === '' || $disciplina === '') {
        responder(['erro' => 'Aluno, turma e disciplina são obrigatórios.'], 422);
    }

    if (mb_strlen($aluno) > 120 || mb_strlen($turma) > 30 ||
        mb_strlen($disciplina) > 100) {
        responder(['erro' => 'Um dos campos de texto excede o limite.'], 422);
    }

    if ($bimestre === false || $bimestre === null ||
        $bimestre < 1 || $bimestre > 4) {
        responder(['erro' => 'O bimestre deve ser de 1 a 4.'], 422);
    }

    if (!is_numeric($nota) || !is_finite((float)$nota) ||
        (float)$nota < 0 || (float)$nota > 10) {
        responder(['erro' => 'A nota deve estar entre 0 e 10.'], 422);
    }

    return [$aluno, $turma, $disciplina, $bimestre, $nota];
}

$metodo = $_SERVER['REQUEST_METHOD'];
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$entrada = json_decode(file_get_contents('php://input'), true);

if ($metodo === 'POST' || $metodo === 'PUT') {
    if (!is_array($entrada)) {
        responder(['erro' => 'Envie um objeto JSON válido.'], 400);
    }
}

try {
    $pdo = conectar();

    switch ($metodo) {
        case 'GET':
            $sql = "
                SELECT b.*,
                    ROUND((
                        SELECT AVG(n.nota)
                        FROM boletim n
                        WHERE n.aluno = b.aluno
                          AND n.turma = b.turma
                          AND n.disciplina = b.disciplina
                    ), 2) AS media,
                    (
                        SELECT COUNT(*)
                        FROM boletim n
                        WHERE n.aluno = b.aluno
                          AND n.turma = b.turma
                          AND n.disciplina = b.disciplina
                    ) AS bimestres
                FROM boletim b
            ";

            if ($id !== null && $id > 0) {
                $st = $pdo->prepare($sql . ' WHERE b.id = ?');
                $st->execute([$id]);
                $registro = $st->fetch();

                $registro
                    ? responder($registro)
                    : responder(['erro' => 'Registro não encontrado.'], 404);
            }

            $sql .= ' ORDER BY b.aluno, b.disciplina, b.bimestre';
            responder($pdo->query($sql)->fetchAll());

        case 'POST':
            [$aluno, $turma, $disciplina, $bimestre, $nota] =
                validar($entrada);

            $st = $pdo->prepare("
                INSERT INTO boletim
                    (aluno, turma, disciplina, bimestre, nota)
                VALUES (?, ?, ?, ?, ?)
            ");
            $st->execute([$aluno, $turma, $disciplina, $bimestre, $nota]);

            responder([
                'id' => (int)$pdo->lastInsertId(),
                'mensagem' => 'Nota cadastrada.'
            ], 201);

        case 'PUT':
            if ($id === false || $id === null || $id < 1) {
                responder(['erro' => 'Informe um id válido na URL.'], 400);
            }

            [$aluno, $turma, $disciplina, $bimestre, $nota] =
                validar($entrada);

            $st = $pdo->prepare("
                UPDATE boletim
                SET aluno = ?, turma = ?, disciplina = ?,
                    bimestre = ?, nota = ?
                WHERE id = ?
            ");
            $st->execute([
                $aluno, $turma, $disciplina, $bimestre, $nota, $id
            ]);

            $check = $pdo->prepare('SELECT id FROM boletim WHERE id = ?');
            $check->execute([$id]);

            $check->fetch()
                ? responder(['mensagem' => 'Nota atualizada.'])
                : responder(['erro' => 'Registro não encontrado.'], 404);

        case 'DELETE':
            if ($id === false || $id === null || $id < 1) {
                responder(['erro' => 'Informe um id válido na URL.'], 400);
            }

            $st = $pdo->prepare('DELETE FROM boletim WHERE id = ?');
            $st->execute([$id]);

            $st->rowCount()
                ? responder(['mensagem' => 'Nota excluída.'])
                : responder(['erro' => 'Registro não encontrado.'], 404);

        default:
            header('Allow: GET, POST, PUT, DELETE');
            responder(['erro' => 'Método não permitido.'], 405);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());

    if ($e->getCode() === '23000') {
        responder(['erro' => 'Já existe uma nota para esse aluno, disciplina e bimestre.'], 409);
    }

    responder(['erro' => 'Erro interno no banco de dados.'], 500);
}