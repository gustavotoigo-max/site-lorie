# 4 · Hospedagem, domínio, banco de dados e e-mail

**O que você precisa contratar**
- **Hospedagem WordPress** (plano compartilhado já basta para começar) com: PHP 8.1 ou mais novo, banco **MySQL/MariaDB**, **SSL grátis (https)**, backup e instalador de WordPress. Exemplos no Brasil: Hostinger, HostGator, Locaweb, KingHost. Compare preço e suporte — muitos planos incluem o domínio no primeiro ano.
- **Domínio** `.com.br` (ex.: `lorie.com.br`) — registrado no <https://registro.br> ou pela própria hospedagem.
- **Banco de dados**: você não instala nada à parte. A hospedagem cria o MySQL quando você instala o WordPress; o Local já traz um no computador.

### Passo 1 — Contratar e apontar o domínio
Contrate a hospedagem e registre o domínio. Se o domínio estiver no registro.br, troque os **DNS** para os que a hospedagem informar (leva de minutos a algumas horas).

### Passo 2 — Instalar o WordPress na hospedagem
No painel da hospedagem, use o **instalador de WordPress** para o seu domínio (ele cria o banco de dados e o `wp-config.php`). Ative o **SSL/https** no mesmo painel.

### Passo 3 — Levar a loja do computador para a hospedagem
Opção simples: no WordPress da hospedagem, repita os passos 3 a 6 do guia 1 (WooCommerce, plugin, tema e assistente — desta vez **sem** os exemplos) e cadastre as peças.
Opção "copiar tudo": instale o plugin **All-in-One WP Migration** no Local, exporte, e importe no site da hospedagem.

### Passo 4 — E-mails que chegam de verdade
Os links de acesso e avisos de pedido são e-mails. Para não caírem no spam, instale o plugin **WP Mail SMTP** e conecte a um serviço de envio (ex.: Brevo, que tem plano gratuito) usando um e-mail do seu domínio (ex.: `contato@lorie.com.br`). Faça o teste de envio do próprio plugin.

### Passo 5 — Backup, segurança e velocidade
Instale **UpdraftPlus** (backup automático para o Google Drive), **Wordfence** (segurança, com verificação em duas etapas para o admin) e o plugin de cache que a hospedagem recomendar (ex.: LiteSpeed Cache). Mantenha WordPress, WooCommerce e plugins atualizados.

### Passo 6 — Ajustes finais
- **Configurações › Links permanentes**: "Nome do post" (o assistente já faz isso).
- **Lorié › Configurações**: confira WhatsApp e remetente.
- Ative o login com Google (guia 3) e passe pelo [checklist](08-checklist-antes-de-publicar.md).
