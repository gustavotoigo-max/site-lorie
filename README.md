# Lorié — loja de crochê feito à mão

Site de vendas da **Lorié** ("De uma geração para outra."): WordPress + WooCommerce com tema e plugin próprios.

- **Tema `lorie`**: visual da loja (slider em tela cheia, catálogo por categorias, página do produto, carrinho lateral, favoritos, Quem somos, Instagram, WhatsApp).
- **Plugin `lorie-core`**: regras da loja (cadastro com CPF, login com Google e por link no e-mail, pagamento de teste `COMPREAQUI`, personalização de peças, painel da artesã com Encomendas, Envios + etiqueta, Produtos e Pagamentos).

> **Versão de testes:** nenhum pagamento real é cobrado. O pedido é aprovado com o código **COMPREAQUI**. O Mercado Pago entra na próxima versão (veja `docs/07`).

## Por onde começar

| # | Guia | Quando |
|---|------|--------|
| 1 | [Rodar a loja no seu computador](docs/01-rodar-no-computador.md) | Primeiro |
| 2 | [Deixar a loja com a cara da Lorié](docs/02-personalizar-a-loja.md) | Depois do 1 |
| 3 | [Ativar o login com Google](docs/03-login-com-google.md) | Quando for publicar |
| 4 | [Hospedagem, domínio, banco de dados e e-mail](docs/04-hospedagem-e-dominio.md) | Para colocar no ar |
| 5 | [Variáveis, senhas e segurança](docs/05-variaveis-e-seguranca.md) | Leitura obrigatória antes de publicar |
| 6 | [Uso diário da artesã](docs/06-uso-diario.md) | Para a dona da loja |
| 7 | [Mercado Pago (próxima versão)](docs/07-mercado-pago-proxima-versao.md) | Planejamento |
| 8 | [Checklist antes de publicar](docs/08-checklist-antes-de-publicar.md) | Antes de divulgar |

Cada guia tem no máximo 6 passos.

## O que o site faz

| Pedido | Onde está |
|---|---|
| Página inicial com slider em tela cheia, pouco texto | `front-page.php` · fotos em **Lorié › Slides do topo** |
| Seções de venda na home | Categorias, Novidades (carrossel), História, Mais amados, Encomendas personalizadas, Benefícios, Instagram, Newsletter |
| Quem somos (foto da dona à esquerda + caixa com a história) | `page-templates/quem-somos.php` · editável em **Aparência › Personalizar › Lorié** |
| Catálogo com seções por tipo (Bolsas, Saias, Blusinhas, Chapéus, Acessórios), preço e botão Comprar | `woocommerce/archive-product.php` |
| Carrinho clássico + carrinho lateral rápido, "Continuar comprando" | `inc/ajax.php`, página Carrinho |
| Pagamento conforme a especificação | `docs/especificacao-pagamentos.md` · **Lorié › Pagamentos** (Pix/cartão/parcelas já guardados para o Mercado Pago, histórico de alterações, reembolso com confirmação e trava contra clique duplo) |
| Pagamento fictício com o código COMPREAQUI | `class-gateway-test.php` (o código pode ser trocado no painel) |
| Botão de WhatsApp | Botão flutuante + "Tirar dúvida" no produto + resumo do pedido no WhatsApp após a compra |
| Painel de admin estilo loja | **Lorié › Painel** (vendas, gráfico, últimos pedidos, estoque baixo) |
| Editar textos e fotos pelo WordPress | **Aparência › Personalizar › Lorié — textos e fotos** (com pré-visualização) |
| Posts do Instagram | **Lorié › Instagram** (manual ou automático com token) |
| Login com Google (com o símbolo do Google) ou cadastro com nome, e-mail, telefone, CPF e endereço | **Minha conta** · CEP preenche o endereço sozinho |
| Encomendas (só a dona) | **Lorié › Encomendas** — quadro + lista de produção |
| Envios com etiqueta em imagem e logo | **Lorié › Envios** → "Gerar etiqueta" (PNG 10×15 cm, com QR code) |
| Criar produto com cores e tamanhos | **Lorié › Novo produto** (as combinações são criadas sozinhas) |

Extras incluídos: favoritos, busca instantânea, compra rápida em janela, personalização por peça, frete grátis com barra de progresso, linha do tempo do pedido para a cliente, e-mails de "em produção" e "enviado" com rastreio, aviso de cookies (LGPD), páginas de privacidade e trocas, metadados para compartilhar no WhatsApp/Instagram.

## Estrutura

```
wp-content/
  themes/lorie/            tema (visual)
  plugins/lorie-core/      plugin (regras da loja e painel)
docs/                      guias passo a passo
instalar/                  lorie-core.zip e lorie-tema.zip (para enviar pelo painel do WordPress)
docker-compose.yml         alternativa ao Local (para quem usa Docker)
.env.example               modelo de variáveis do Docker (copie para .env)
```

## Tecnologia

WordPress 6.5+ · WooCommerce 9+ · PHP 8.0+ · MySQL/MariaDB.
No navegador: Swiper (sliders), GSAP + ScrollTrigger (animações), Lenis (rolagem suave), qrcode-generator (etiqueta). Tudo incluído no repositório — sem dependência de CDN.

Nenhuma senha, chave ou dado de cliente fica neste repositório (veja `docs/05`).
