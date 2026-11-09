CREATE DATABASE IF NOT EXISTS boletim
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE boletim;

CREATE TABLE IF NOT EXISTS boletim (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno VARCHAR(120) NOT NULL,
    turma VARCHAR(30) NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    bimestre TINYINT UNSIGNED NOT NULL,
    nota DECIMAL(4,2) NOT NULL,
    criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_bimestre
        CHECK (bimestre BETWEEN 1 AND 4),

    CONSTRAINT chk_nota
        CHECK (nota BETWEEN 0 AND 10),

    CONSTRAINT uq_nota
        UNIQUE (aluno, turma, disciplina, bimestre)
);

INSERT INTO boletim
    (aluno, turma, disciplina, bimestre, nota)
VALUES
    ('Ana Silva', '9º A', 'Matemática', 1, 8.5),
    ('Ana Silva', '9º A', 'Português', 1, 9.0),
    ('Ana Silva', '9º A', 'Ciências', 1, 7.5),
    ('Pedro Santos', '9º A', 'Matemática', 1, 6.0),
    ('Pedro Santos', '9º A', 'Português', 1, 7.0),
    ('Pedro Santos', '9º A', 'Ciências', 1, 5.5);