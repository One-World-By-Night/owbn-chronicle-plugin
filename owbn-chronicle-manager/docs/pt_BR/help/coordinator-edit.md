# Editar um escritório de coordenação

A ficha de um escritório: descrição, mandato, subcoordenadores, histórico e links. Todo site da OWbN mostra o que é salvo aqui.

## Quem pode usar

- O coordenador e os subcoordenadores do escritório. Num escritório administrativo, o coordenador e a staff dele.
- Os administradores do site, a equipe executiva e a equipe de Web, em todos os escritórios e todos os campos.

## Como chegar

wp-admin em council.owbn.net → **OWBN C&C** → **All Coordinators** → clique no nome do escritório.

## A tela

- Seis abas. Coordenadores e subcoordenadores veem quatro; **Basic Info** e **Coordinator** são só para os administradores do site.
  - **Basic Info** - **Coordinator Slug**, **Office Title**, **Appointment** (Elected ou Appointed) e **Type** (Administrative, Genre ou Clan).
  - **Description** - **Hosting Chronicle** (a crônica sede de um escritório de gênero ou de clã), **Website URL**, **Term Start Date** e **Term End Date**, e abaixo deles a descrição pública do escritório: horário de atendimento, como os pedidos de R&U chegam ao escritório, o que mais os membros precisarem saber.
  - **Coordinator** - quem ocupa o escritório.
  - **Staff** - os **Sub-Coordinators**, veja abaixo.
  - **History** - **Previous Coordinators**, um registro só de leitura de cada coordenador anterior e do mandato dele.
  - **Links** - **Document Links**, **Email Lists**, **Discord Lists** e **Player Lists**.
- Na lateral: **Publish**, com **Update**, e **Revisions**.

### Subcoordenadores

Cada subcoordenador tem:

- **User** - a conta dele neste site, que recebe o cargo de subcoordenador. Para quem ainda não tem conta, deixe **User** vazio e escolha a conta quando ela existir.
- **Display Name** e **Display Email** - mostrados na página pública do escritório.
- **Role** - a função dele no escritório, se tiver uma.
- **Actual Email** - o endereço pessoal dele, visto só pelo coordenador e pela staff do escritório e pelos administradores do site.

**Add** acrescenta um subcoordenador; **Remove** tira um. Escolher uma conta não preenche os nomes nem os emails; digite-os.

## Tarefas comuns

### Atualizar os dados do escritório

1. Mude o que precisar em qualquer aba.
2. Clique em **Update**.

### Mudar os subcoordenadores

1. Em **Staff**, acrescente, tire ou mude subcoordenadores.
2. Clique em **Update**.
3. Se você não é administrador do site, a mudança agora espera aprovação, e o topo da página avisa. As suas outras mudanças estão salvas.

### Passar o escritório a um novo coordenador

Só administradores do site.

1. Em **Coordinator**, escolha a conta do novo coordenador e preencha o nome e os emails dele.
2. Em **Description**, defina o novo **Term Start Date** e o **Term End Date**.
3. Clique em **Update**. O coordenador que sai vai para **History**, com o mandato terminando hoje, e perde o cargo de coordenador; o novo o recebe.

## Bom saber

- Tirar alguém de **Staff** encerra o cargo dele na hora, mesmo enquanto a mudança espera aprovação. Se a mudança for rejeitada, ele recebe o cargo de volta.
- Restaurar uma revisão deixa o coordenador, os subcoordenadores e os campos de **Basic Info** como estão, a menos que um administrador do site a restaure.
- Só os administradores do site, a equipe executiva e a equipe de Web mudam o status ou a visibilidade de um escritório, ou o mandam para a lixeira.
- Os outros sites da OWbN guardam uma cópia do escritório por até uma hora, então uma mudança pode levar esse tempo para aparecer em todo lugar.

## Solução de problemas

- **Sem as abas Basic Info e Coordinator.** Elas são para os administradores do site. Peça à equipe de Web para mudar o título, o tipo ou o coordenador do escritório.
- **"Some fields could not be saved. All other edits WERE saved."** Os campos citados mantiveram o valor antigo. Corrija-os e clique em **Update** de novo.
- **A sua mudança de staff não apareceu.** Ela está esperando aprovação; a barra **Pending Staff Changes** avisa.

## Relacionados

- [Escritórios de coordenação](coordinators.md)
- [Pending Staff Changes](pending-changes.md)
- [Solução de problemas do C&C Manager](troubleshooting.md)
