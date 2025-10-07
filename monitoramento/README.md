# Projeto Monitoramento de Máquinas

Este projeto cria um **dashboard web** para monitoramento do tempo de atividade de máquinas usando dados enviados por ESP32 com sensor MPU6050. O backend é PHP/MySQL e o frontend usa Bootstrap + Chart.js.

---

## 1️⃣ Requisitos

- XAMPP (Apache + MySQL)
- PHP >= 8.0
- Navegador moderno

## 2️⃣ Estrutura do projeto

```
monitoramento/
├─ index.php
├─ conexao.php
├─ etl_diario.php
├─ etl_mensal.php
├─ etl_anual.php
├─ api/
│  └─ consolidado.php
```

## 3️⃣ Configuração do banco

1. Abra o **phpMyAdmin**: `http://localhost/phpmyadmin/`
2. Crie um novo banco chamado `monitoramento_maquinas`
3. Execute o script SQL `banco.sql` fornecido para criar todas as tabelas necessárias.

## 4️⃣ Configuração do projeto

1. Copie a pasta `monitoramento` para `C:\xampp\htdocs\`  
2. Abra `conexao.php` e confirme que o nome do banco está correto:

```php
$dbname = "monitoramento_maquinas";
```

3. Adicione algumas máquinas na tabela `maquinas`:

```sql
INSERT INTO maquinas (nome) VALUES ('Máquina 1'), ('Máquina 2');
```

4. Execute os scripts de ETL:

```bash
php etl_diario.php
php etl_mensal.php
php etl_anual.php
```

> Dica: agende os scripts via **Agendador de Tarefas do Windows** ou `cron` no Linux.

## 5️⃣ Acessando o Dashboard

- Abra: `http://localhost/monitoramento/index.php`
- Use os filtros de máquina e tipo (diário, mensal, anual) para visualizar os gráficos.

## 6️⃣ Observações

- `leituras` → dados brutos do ESP32.
- ETLs → populam `consolidado_diario`, `consolidado_mensal`, `consolidado_anual`.
- Dashboard usa **Chart.js** para gráficos dinâmicos e responsivos.



## 7️⃣ Testando ESP com o postman

### GET
- URL: http://localhost/monitoramento/salvar.php?maquina_id=1&vibrando=1
- Método: GET

### POST
- URL: http://localhost/monitoramento/salvar.php
- Método: POST
- Body JSON:
{
  "maquina_id": 1,
  "vibrando": 1
}
