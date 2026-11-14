# Crônicas

A lista das crônicas da OWbN em chronicles.owbn.net, e onde se acrescenta uma crônica nova.

## Quem pode usar

- Os administradores do site, a equipe executiva e a equipe de Web veem todas as crônicas e acrescentam novas.
- Um HST, CM ou AST vê só a sua própria crônica, e o CM da crônica principal de uma satélite vê a satélite também.

## Como chegar

wp-admin em chronicles.owbn.net → **OWBN C&C** → **All Chronicles** (a tela se chama **Chronicles**).

## A tela

- Uma linha por crônica. Clique no nome para abri-la, veja [Editar uma crônica](chronicle-edit.md).
- **Compliance** - **OK** quando a crônica tem os três documentos obrigatórios; senão, quantos faltam. Passe o mouse sobre o selo para ver quais.
- **All**, **Published**, **Draft** e **Decommissioned** acima da lista mostram as crônicas em cada situação. Uma crônica desativada (**Decommissioned**) fechou; a ficha dela continua.
- **View** sob um nome abre a página pública da crônica.
- **Add New Chronicle**, ao lado do título, para os administradores do site, a equipe executiva e a equipe de Web.
- Todos os outros veem "Showing only chronicles you have access to."

## Tarefas comuns

### Acrescentar uma crônica nova

1. Em sso.owbn.net, vá a **Users** → **accessSchema** e acrescente os cargos da crônica, um por linha: `chronicle/<slug>/hst`, `chronicle/<slug>/cm`, `chronicle/<slug>/staff` e `chronicle/<slug>/player`. Uma satélite não tem o cargo `cm`. Clique em **Add Role(s)**.
2. Aqui, clique em **Add New Chronicle**.
3. Dê como título a cidade, o estado e o país, e depois o nome da crônica: "Harbor City, MA - USA, Tides of Ash".
4. Defina o **Chronicle Slug**: de 2 a 8 letras minúsculas e números. Ele não muda depois de salvo.
5. Marque **Probationary?** ou **Satellite?** se for o caso. Uma satélite precisa também da sua **Parent Chronicle**.
6. Em **Game, Schedule & Staff**, escolha os **Genres** e o **Game Type**, e preencha **HST Info** e, se não for uma satélite, **CM Info**.
7. Em **Documents, Players & Sites**, dê às House Rules, à Disciplinary Policy e à Accessibility Policy um link ou um arquivo.
8. Clique em **Publish**. O HST, o CM e os ASTs recebem os cargos em sso.owbn.net.

### Fechar uma crônica

Só administradores do site.

1. Abra a crônica.
2. Em **Publish**, mude **Status** para **Decommissioned** e clique em **Update**.

## Bom saber

- O slug é permanente: ele está nos cargos da crônica, no endereço dela e no email do CM, `<slug>-cm@owbn.net`.
- Salvar alguém da staff antes de os cargos da crônica existirem em sso.owbn.net não lhe dá cargo nenhum, e nada avisa. Acrescente os cargos primeiro.
- Feche uma crônica desativando-a. Só os administradores do site, a equipe executiva e a equipe de Web mudam o status de uma crônica ou a mandam para a lixeira.
- Os outros sites da OWbN guardam uma cópia da lista por até uma hora.

## Solução de problemas

- **Sem o botão Add New Chronicle, ou "You do not have permission to create new items."** Só os administradores do site, a equipe executiva e a equipe de Web acrescentam crônicas.
- **A sua crônica não está na lista.** A lista segue o seu cargo de HST, CM ou staff nela. Um cargo dado nos últimos dez minutos pode ainda não contar; recarregue depois. Sem cargo nenhum, fale com a equipe de Web.
- **O HST ou o CM não recebeu cargo.** Os cargos da crônica não existiam em sso.owbn.net quando eles foram salvos, ou foram escolhidos como **[New User]**. Acrescente os cargos, escolha a conta deles e clique em **Update**.

## Relacionados

- [Editar uma crônica](chronicle-edit.md)
- [HST & AST Report](hst-ast-report.md)
- [Visão geral do OWBN C&C Manager](../overview.md)
