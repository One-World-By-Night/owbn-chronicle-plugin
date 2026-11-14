# Escritórios de coordenação

A lista dos escritórios de coordenação da OWbN em council.owbn.net, e onde se acrescenta um escritório novo.

## Quem pode usar

- Os administradores do site, a equipe executiva e a equipe de Web veem todos os escritórios e acrescentam novos.
- Um coordenador ou subcoordenador vê só o seu próprio escritório.

## Como chegar

wp-admin em council.owbn.net → **OWBN C&C** → **All Coordinators** (a tela se chama **Coordinators**).

## A tela

- Uma linha por escritório. Clique no nome para abri-lo, veja [Editar um escritório de coordenação](coordinator-edit.md).
- **All**, **Published**, **Draft** e **Decommissioned** acima da lista mostram os escritórios em cada situação.
- **View** sob um nome abre a página pública do escritório.
- **Add New Coordinator**, ao lado do título, para os administradores do site, a equipe executiva e a equipe de Web.
- Todos os outros veem "Showing only coordinators you have access to."

## Tarefas comuns

### Acrescentar um escritório novo

1. Em sso.owbn.net, vá a **Users** → **accessSchema** e acrescente os cargos do escritório, um por linha. Um escritório de gênero ou de clã recebe `coordinator/<slug>/coordinator` e `coordinator/<slug>/sub-coordinator`; um escritório administrativo recebe `exec/<slug>/coordinator` e `exec/<slug>/staff`. Clique em **Add Role(s)**.
2. Aqui, clique em **Add New Coordinator**.
3. Dê como título o nome do escritório, como "Sabbat Coordinator".
4. Em **Basic Info**, defina o **Coordinator Slug** (letras minúsculas, números e hifens), o **Office Title**, o **Appointment** e o **Type**.
5. Em **Coordinator**, escolha a conta do coordenador e preencha o nome e os emails dele.
6. Clique em **Publish**. O coordenador recebe o cargo de coordenador do escritório em sso.owbn.net.

### Fechar um escritório

Só administradores do site.

1. Abra o escritório.
2. Em **Publish**, mude **Status** para **Decommissioned** e clique em **Update**.

## Bom saber

- O **Type** de um escritório define os cargos dele: os de um escritório administrativo começam com `exec/`, os de um escritório de gênero ou de clã com `coordinator/`.
- O slug não muda depois de salvo.
- Feche um escritório desativando-o. Só os administradores do site, a equipe executiva e a equipe de Web mudam o status de um escritório ou o mandam para a lixeira.
- Acrescente os cargos do escritório em sso.owbn.net antes de salvar o coordenador, senão ninguém recebe cargo.
- Os outros sites da OWbN guardam uma cópia da lista por até uma hora.

## Solução de problemas

- **Sem o botão Add New Coordinator, ou "You do not have permission to create new items."** Só os administradores do site, a equipe executiva e a equipe de Web acrescentam escritórios.
- **O seu escritório não está na lista.** A lista segue o seu cargo de coordenador ou subcoordenador nele. Um cargo dado nos últimos dez minutos pode ainda não contar; recarregue depois. Sem cargo nenhum, fale com a equipe de Web.

## Relacionados

- [Editar um escritório de coordenação](coordinator-edit.md)
- [Pending Staff Changes](pending-changes.md)
- [Visão geral do OWBN C&C Manager](../overview.md)
