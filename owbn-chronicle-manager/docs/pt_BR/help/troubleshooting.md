# Solução de problemas do C&C Manager

Soluções para o que mais dá errado ao editar uma crônica ou um escritório de coordenação: campos que não salvam, acesso que falta e cargos que não batem com a ficha.

## Quem pode usar

Quem edita uma crônica ou um escritório. Algumas soluções pedem um administrador do site.

## Como chegar

A maioria das soluções começa em wp-admin → **OWBN C&C** → **All Chronicles** ou **All Coordinators** → o nome da ficha.

## A tela

A maioria das soluções usa a tela de edição da ficha: as barras do topo, a aba marcada ⚠ e **Revisions** na lateral. As mudanças de staff que esperam estão em [Pending Staff Changes](pending-changes.md).

## Tarefas comuns

### Alguém não recebeu cargo ao ser posto num posto

1. Se a ficha tem a barra **Pending Staff Changes**, a mudança está esperando aprovação.
2. Confira se o **User** dele é a conta dele, e não **[New User]** nem vazio.
3. Confira se os cargos da ficha existem em sso.owbn.net em **Users** → **accessSchema**, como `chronicle/<slug>/staff`.
4. Corrija o que faltava e clique em **Update** com ele no posto.

### Desfazer uma mudança

1. Em **Revisions**, clique em **Browse**.
2. Ache a versão e clique em **Restore This Revision**.

## Bom saber

- As listas e as telas de edição seguem os seus cargos do accessSchema, conferidos de novo a cada dez minutos.
- Os outros sites da OWbN guardam uma cópia de cada ficha por até uma hora.
- Restaurar uma revisão deixa a staff e os campos só de administrador como estão, a menos que um administrador do site a restaure.

## Solução de problemas

- **"Some fields could not be saved. All other edits WERE saved."** Os campos citados mantiveram o valor antigo, e a aba deles mostra ⚠. Corrija-os e clique em **Update** de novo. Um posto da staff que precisa ser preenchido pede **User**, **Display Name** e **Display Email**.
- **"Invalid or duplicate slug."** O slug de uma crônica tem de 2 a 8 letras minúsculas e números, e não pode estar em uso. Uma crônica nova com slug inválido é salva como rascunho.
- **Um campo está em cinza, ou falta uma aba.** Só os administradores do site mudam o nome, o slug, probatória, satélite, crônica principal, data de início e região de uma crônica, ou **Basic Info** e **Coordinator** de um escritório.
- **Uma mudança de staff não apareceu.** Ela está esperando aprovação.
- **"Sorry, you are not allowed to edit this item." ou "You do not have permission to edit this Chronicle."** Você não tem cargo nessa crônica ou nesse escritório. Fale com a equipe de Web.
- **A sua crônica ou o seu escritório não está na lista.** Um cargo dado nos últimos dez minutos pode ainda não contar; recarregue depois.
- **Alguém manteve o cargo depois de sair do posto.** Ele o tem por outro caminho, como um segundo posto. Um administrador do site pode conferir em sso.owbn.net.
- **Uma mudança não aparece em outro site da OWbN.** Aquele site ainda tem a cópia antiga. O administrador dele pode clicar em **Refresh All Cache** em **OWBN Core** → **Settings**.
- **"Required documents missing."** Dê às House Rules, à Disciplinary Policy e à Accessibility Policy um link ou um arquivo em **Document Links**. A crônica é publicada mesmo sem eles.

## Relacionados

- [Editar uma crônica](chronicle-edit.md)
- [Editar um escritório de coordenação](coordinator-edit.md)
- [Pending Staff Changes](pending-changes.md)
- [Visão geral do OWBN C&C Manager](../overview.md)
