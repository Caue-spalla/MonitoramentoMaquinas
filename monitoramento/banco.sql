CREATE DATABASE IF NOT EXISTS monitoramento_maquinas;
USE monitoramento_maquinas;

CREATE TABLE maquinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

CREATE TABLE leituras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maquina_id INT NOT NULL,
    vibrando TINYINT(1) NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id)
);

CREATE TABLE consolidado_diario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maquina_id INT NOT NULL,
    data DATE NOT NULL,
    percentual_atividade FLOAT NOT NULL,
    UNIQUE(maquina_id, data),
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id)
);

CREATE TABLE consolidado_mensal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maquina_id INT NOT NULL,
    ano INT NOT NULL,
    mes INT NOT NULL,
    percentual_atividade FLOAT NOT NULL,
    UNIQUE(maquina_id, ano, mes),
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id)
);

CREATE TABLE consolidado_anual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maquina_id INT NOT NULL,
    ano INT NOT NULL,
    percentual_atividade FLOAT NOT NULL,
    UNIQUE(maquina_id, ano),
    FOREIGN KEY (maquina_id) REFERENCES maquinas(id)
);
