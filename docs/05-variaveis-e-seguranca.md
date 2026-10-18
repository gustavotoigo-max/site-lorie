# 5 · Variáveis, senhas e segurança

O repositório é **público**. Ele contém só código e imagens — nenhuma senha. Veja onde cada informação sensível mora:

| Informação | Onde fica | Vai para o GitHub? |
|---|---|---|
| Usuário/senha do banco de dados (`DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`) | `wp-config.php` na hospedagem | **Nunca** (está no `.gitignore`) |
| Chaves de segurança do WordPress (`AUTH_KEY`, `SECURE_AUTH_KEY`… "salts") | `wp-config.php` | **Nunca** |
| Google Client ID / Client Secret | Banco de dados (Lorié › Configurações) **ou** `wp-config.php` | **Nunca** |
| Token do Instagram | Banco de dados (Lorié › Instagram) | **Nunca** |
| Chaves do Mercado Pago (próxima versão) | `wp-config.php` | **Nunca** |
| Dados de clientes e pedidos | Banco de dados | **Nunca** |
| Fotos enviadas (`wp-content/uploads`) | Hospedagem | Não (`.gitignore`) |
| Senhas do Docker (opcional) | arquivo `.env` (copiado do `.env.example`) | **Nunca** |

### Passo 1 — Entender o `wp-config.php`
É criado automaticamente pelo instalador (Local ou hospedagem). Ele guarda as variáveis do banco e as chaves. Não edite se não precisar; nunca o envie para o GitHub nem por WhatsApp.

### Passo 2 — (Opcional) Guardar segredos no `wp-config.php`
Se preferir não deixar as chaves do Google no banco, acrescente antes da linha `/* That's all, stop editing! */`:
```php
define( 'LORIE_GOOGLE_CLIENT_ID', 'cole-aqui' );
define( 'LORIE_GOOGLE_CLIENT_SECRET', 'cole-aqui' );
```
Quando essas constantes existem, os campos do painel ficam bloqueados.

### Passo 3 — Contas do painel
Crie a conta da artesã com o perfil **Gerente de loja** (Usuários › Adicionar novo) — ela acessa Encomendas, Envios, Produtos e Pagamentos sem poder mexer em plugins. Use senhas fortes e ative 2 etapas (Wordfence).

### Passo 4 — HTTPS sempre
Com o site no ar, confirme que abre com `https://` (cadeado). Login, cadastro e (no futuro) pagamento exigem https.

### Passo 5 — Antes de cada `git push`
Rode `git status` e confira que não aparece `wp-config.php`, `.env`, `*.sql` ou `uploads/`. O `.gitignore` já bloqueia esses arquivos.

### Passo 6 — LGPD
O cadastro pede consentimento e a Política de privacidade é criada pelo assistente — revise o texto. Atenda pedidos de exclusão em **Ferramentas › Apagar dados pessoais** do WordPress.
