# 3 · Ativar o login com Google

O botão "Entrar com Google" já aparece na loja. Para funcionar, crie uma credencial gratuita no Google.
> O Google só aceita endereços com **https** (site publicado) ou **localhost**. No Local, o endereço `lorie.local` não funciona — faça isto depois de publicar (guia 4), ou mude o Local para modo localhost em *Preferences › Advanced › Router mode*.

### Passo 1 — Criar o projeto
Acesse <https://console.cloud.google.com>, entre com a conta Google da loja › seletor de projetos no topo › **Novo projeto** › nome `Loja Lorie` › **Criar**.

### Passo 2 — Tela de consentimento
Menu › **APIs e serviços › Tela de permissão OAuth** (ou "Google Auth Platform › Branding") › tipo **Externo** › nome do app `Lorié`, e-mail de suporte, logo (opcional) › salve. Em **Público/Audience**, clique em **Publicar app** (senão só contas de teste conseguem entrar).

### Passo 3 — Criar a credencial
**APIs e serviços › Credenciais › Criar credenciais › ID do cliente OAuth** › tipo **Aplicativo da Web** › nome `Site Lorié`.

### Passo 4 — Endereço de retorno
Em **URIs de redirecionamento autorizados**, cole o endereço que aparece em **Lorié › Configurações › Login com Google**. Ele tem este formato:
`https://SEU-DOMINIO/wp-json/lorie/v1/google/callback`
Clique em **Criar**.

### Passo 5 — Copiar as chaves para o site
O Google mostra o **Client ID** e o **Client Secret**. Cole os dois em **Lorié › Configurações › Login com Google** e salve. O status muda para "configurado".
> O *Client Secret* é secreto: fica só no banco de dados do site (ou no `wp-config.php`, veja o guia 5). Nunca coloque no GitHub.

### Passo 6 — Testar
Abra a loja numa janela anônima › **Minha conta** › **Entrar com Google**. Na primeira vez, a cliente completa telefone, CPF e endereço e já pode comprar.
