# Sistema de Controle de Carga Horária de Médicos

Aplicação web desenvolvida em PHP com MySQL para controle de carga horária de profissionais médicos vinculados à associação.

## Requisitos

- PHP 8.0 ou superior com extensões `mysqli`, `iconv` e `intl` habilitadas
- Servidor HTTP (Apache, Nginx ou PHP Built-in server)
- MySQL 5.7+ ou MariaDB

## Instalação

1. Clone o repositório e acesse a pasta do projeto.
2. Crie o banco de dados executando o script `schema.sql` no MySQL (ele também cria usuários `controle_app` e `controle_admin` com as permissões necessárias; ajuste as senhas no script conforme necessidade):

   ```sql
   SOURCE schema.sql;
   ```
3. Ajuste as credenciais de banco no arquivo `config/db.php` caso necessário, preferencialmente utilizando o usuário `controle_app` criado pelo script.
4. Inicie o servidor embutido do PHP apontando para a pasta `public`:

   ```bash
   php -S localhost:8000 -t public
   ```
5. Acesse `http://localhost:8000` no navegador.

## Funcionalidades

- Cadastro de profissionais com nome, empresa, unidade, CBO, carga horária mensal e valor da hora.
- Registro de observações mensais com possibilidade de adicionar ou remover horas (extras/faltas).
- Controle mensal de pagamento do contrato (pago/pendente) por profissional.
- Listagem dos profissionais com resumo de carga horária base, horas extras, faltas e total consolidado.
- Geração de relatório imprimível com todas as informações essenciais.

## Geração do Relatório

Na página principal existe um botão "Imprimir Relatório" que abre uma nova aba com o resumo dos profissionais, suas unidades, especialidades (CBO), horas base, extras, faltas e total consolidado para o mês selecionado. A interface já aciona a janela de impressão do navegador para facilitar a geração de PDF ou o envio direto para impressora.

## Observações

- O arquivo `config/db.php` utiliza variáveis de ambiente (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) caso disponíveis.
