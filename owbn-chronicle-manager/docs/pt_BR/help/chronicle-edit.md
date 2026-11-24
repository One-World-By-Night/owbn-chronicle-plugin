# Editar uma crônica

A ficha inteira de uma crônica: descrição, jogos, staff, documentos e listas. Todo site da OWbN mostra o que é salvo aqui.

## Quem pode usar

- O HST, o CM e os ASTs da crônica: quem tem `chronicle/<slug>/hst`, `cm` ou `staff`.
- O CM da crônica principal de uma satélite.
- Os administradores do site, a equipe executiva e a equipe de Web, em todas as crônicas e todos os campos.

## Como chegar

wp-admin em chronicles.owbn.net → **OWBN C&C** → **All Chronicles** → clique no nome da crônica.

## A tela

- **Title** - o nome da crônica. Depois de salvo, só um administrador do site pode mudá-lo.
- Barras acima dos campos:
  - **LOCAL** - você está editando a própria ficha da crônica.
  - **Required documents missing** - os documentos obrigatórios que ainda não têm link nem arquivo.
  - **Pending Staff Changes** - uma mudança de staff esperando aprovação.
- **Chronicle Slug**, **Probationary?**, **Satellite?** e, numa satélite, **Parent Chronicle**. Só os administradores do site os mudam; os demais os veem em cinza.
- Cinco abas. Uma aba com um campo que não foi salvo mostra ⚠.
  - **Description** - do que trata a crônica, mostrado na página pública dela.
  - **Game, Schedule & Staff**
    - **Genres**, **Game Type** (In-Person, Virtual ou Hybrid), **Language** (o idioma em que os jogos acontecem; o Find a Game do site da OWbN filtra por ele), **OOC Location** e **Timezone**.
    - **Session List** - cada jogo ou encontro regular: o que é, com que frequência, o dia, o horário de check-in e o de início. **Every Other** pede também uma data de início.
    - **One-Off Events** - datas avulsas, fora da agenda.
    - **HST Info**, **CM Info** e **AST Info** - a staff, veja abaixo.
  - **Documents, Players & Sites** - **Document Links**, **Active Players**, **Game Sites**, **Website URL** e **Social Media URLs**. Document Links sempre tem uma linha para House Rules, uma para Disciplinary Policy e uma para Accessibility Policy.
  - **Premise, Travel & Lists** - **Premise**, **Information for Travellers**, **Staff Lists** e **Player Lists**.
  - **Administrative** - como o HST, o CM e os ASTs são escolhidos; **Start Date** e **Region**, que só os administradores do site mudam; e **Previous Staff**, um registro só de leitura de todos que deixaram um posto da staff.
- Na lateral: **Publish**, com **Update**; **OWBN Dues**, o ano até o qual a anuidade da crônica está paga, um pagamento que chegou e espera a confirmação da tesouraria, a situação do ano atual e dos dois seguintes ("Paid" é paga, "Waived" é dispensada, "Payment pending" é pagamento pendente de confirmação, "Partly paid" é paga em parte, "Unpaid" é não paga e "Not paid yet" é ainda não paga), e o último pagamento; e **Revisions**.

### Postos da staff

Cada pessoa em **HST Info**, **CM Info** e **AST Info** tem:

- **User** - a conta dela neste site, que recebe o cargo do posto. **[New User]** é alguém sem conta: o nome vai para a ficha, mas nenhum cargo vai junto.
- **Display Name** e **Display Email** - mostrados na página pública da crônica.
- **Actual Email** - o endereço pessoal dela. A staff e os administradores o veem aqui; ele nunca aparece em público nem em outro site.
- Os ASTs têm também um **Role**, como Rules ou Plot. **Add** acrescenta um AST; **Remove** tira um.

Escolher uma conta não preenche os nomes nem os emails; digite-os. O Display Email do CM é sempre `<slug>-cm@owbn.net`. Uma satélite não tem CM próprio: usa o da crônica principal.

## Tarefas comuns

### Atualizar os dados da crônica

1. Mude o que precisar em qualquer aba.
2. Clique em **Update**.

### Mudar a staff

1. Em **Game, Schedule & Staff**, mude **HST Info**, **CM Info** ou **AST Info**: escolha a conta de cada pessoa em **User** e preencha os nomes e emails dela.
2. Clique em **Update**.
3. Se você não é administrador do site, a mudança agora espera aprovação, e o topo da página avisa. As suas outras mudanças estão salvas.

### Acrescentar um documento obrigatório

1. Em **Documents, Players & Sites**, abra a linha do documento em **Document Links** (ela está marcada "required — needs URL or upload").
2. Ponha o link, ou envie o arquivo.
3. Clique em **Update**. A barra **Required documents missing** some quando os três tiverem um.

### Desfazer uma mudança

1. Em **Revisions**, clique em **Browse**.
2. Ache a versão que você quer e clique em **Restore This Revision**.

## Bom saber

- Tirar alguém de um posto da staff encerra o cargo dele na hora, mesmo enquanto a mudança espera aprovação. Se a mudança for rejeitada, ele recebe o cargo de volta.
- Uma mudança de staff feita por quem não é administrador do site espera aprovação, com a crônica publicada ou não.
- Restaurar uma revisão deixa a staff e os campos só de administrador como estão, a menos que um administrador do site a restaure.
- Só os administradores do site, a equipe executiva e a equipe de Web mudam o status ou a visibilidade de uma crônica, ou a mandam para a lixeira.
- Os outros sites da OWbN guardam uma cópia da crônica por até uma hora, então uma mudança pode levar esse tempo para aparecer em todo lugar.

## Solução de problemas

- **"Sorry, you are not allowed to edit this item." ou "You do not have permission to edit this Chronicle."** Você não tem cargo de HST, CM ou staff nela. Fale com a equipe de Web.
- **"Some fields could not be saved. All other edits WERE saved."** Os campos citados, marcados em vermelho e com a aba marcada ⚠, mantiveram o valor antigo. Corrija-os e clique em **Update** de novo. Um posto da staff que precisa ser preenchido pede **User**, **Display Name** e **Display Email**.
- **"Invalid or duplicate slug."** O slug tem de ter de 2 a 8 letras minúsculas e números, e não pode estar em uso.
- **Um campo está em cinza.** É um que só um administrador do site muda.
- **A sua mudança de staff não apareceu.** Ela está esperando aprovação; a barra **Pending Staff Changes** avisa.
- **OWBN Dues diz "Dues status is unavailable right now."** Este site não alcançou council.owbn.net. Tente de novo mais tarde.
- **OWBN Dues diz "Payment received, pending confirmation".** O pagamento da crônica chegou. A tesouraria da OWBN o confirma, e então a caixa mostra **Paid through**. Não há nada a fazer. Se você pagou há um minuto e ainda vê "No dues paid on record", recarregue a página.

## Relacionados

- [Crônicas](chronicles.md)
- [Pending Staff Changes](pending-changes.md)
- [Solução de problemas do C&C Manager](troubleshooting.md)
