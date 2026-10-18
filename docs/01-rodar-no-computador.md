# 1 · Rodar a loja no seu computador (grátis)

Vamos usar o **Local**, um programa gratuito que cria um WordPress completo (com banco de dados) no seu computador. Nada vai para a internet ainda.

Antes de começar, tenha à mão os dois pacotes de instalação, que ficam na pasta [`instalar/`](../instalar) do repositório (abra cada arquivo no GitHub e clique em **Download raw file**):
- `lorie-core.zip` — o plugin
- `lorie-tema.zip` — o tema

> Não descompacte: o WordPress recebe o .zip direto. (Para gerar de novo depois de alterar o código: `scripts/gerar-zips.sh`.)

---

### Passo 1 — Instalar o Local
Baixe em <https://localwp.com> (escolha Windows), instale e abra. Pode pular a criação de conta.

### Passo 2 — Criar o site
Clique em **+** (canto inferior esquerdo) › **Create a new site** › nome: `lorie` › **Preferred** › crie um usuário e senha de administrador (anote!) › **Add site**.
Quando terminar, clique em **WP Admin** e entre com esse usuário.

### Passo 3 — Instalar o WooCommerce
No painel: **Plugins › Adicionar novo** › busque **WooCommerce** › **Instalar agora** › **Ativar**.
Se aparecer o assistente do WooCommerce, pode fechar/pular — o nosso assistente configura tudo.

### Passo 4 — Instalar o plugin da Lorié
**Plugins › Adicionar novo › Enviar plugin** › escolha `lorie-core.zip` › **Instalar agora** › **Ativar plugin**.

### Passo 5 — Instalar o tema da Lorié
**Aparência › Temas › Adicionar novo tema › Enviar tema** › escolha `lorie-tema.zip` › **Instalar agora** › clique em **Ativar** na tela seguinte (sem isso o site continua com o visual padrão do WordPress, o "Twenty Twenty-Five").

### Passo 6 — Configurar a loja com um clique
No menu lateral, clique em **Lorié › Assistente** › deixe marcado "Incluir peças, slides e fotos de exemplo" › **Configurar loja agora** (leva cerca de 1 minuto).
Depois clique em **Ver loja ↗** e faça uma compra de teste: escolha uma peça › Comprar › Finalizar compra › crie um cadastro › no pagamento digite **COMPREAQUI**.

---

✅ Pronto! A loja está rodando. Próximo guia: [2 · Deixar a loja com a cara da Lorié](02-personalizar-a-loja.md).

**Se o site aparecer "estranho"** (menu com Cart, Checkout, Sample Page e rodapé "Twenty Twenty-Five"): o tema Lorié não está ativo. Vá em **Aparência › Temas** e clique em **Ativar** no Lorié — ou rode **Lorié › Assistente** de novo, que ele ativa o tema e limpa as páginas duplicadas.

**Dicas**
- Os e-mails (ex.: link de acesso da cliente) não saem do computador: no Local, abra a aba **Tools › Mailpit** para vê-los.
- Quer mostrar a loja para alguém sem publicar? No Local, use **Live Link** (gera um endereço temporário).
- Usa Docker? Existe uma alternativa ao Local: veja o `docker-compose.yml` e o `.env.example` na raiz do projeto.
