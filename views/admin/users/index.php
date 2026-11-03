<?php /** @var array $users */ ?>
<div class="page-head">
  <p class="muted">Administrators can do everything. Editors can manage content, the knowledge base and the homepage, but not users, site settings or API keys.</p>
  <a class="btn btn-primary" href="<?= e(url('/admin/users/new')) ?>"><?= icon('plus', 'icon icon-sm') ?>Add user</a>
</div>
<div class="card card-flush">
  <table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last sign-in</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $user): ?>
      <tr>
        <td><a class="strong" href="<?= e(url('/admin/users/' . $user['id'])) ?>"><?= e($user['name']) ?></a><?= (int) $user['id'] === (int) current_admin()['id'] ? ' <span class="badge">You</span>' : '' ?></td>
        <td><?= e($user['email']) ?></td>
        <td><span class="badge <?= $user['role'] === 'admin' ? 'badge-violet' : '' ?>"><?= e(ucfirst($user['role'])) ?></span></td>
        <td class="meta"><?= $user['last_login_at'] ? e(time_ago($user['last_login_at'])) : 'Never' ?></td>
        <td class="actions">
          <a class="btn btn-sm" href="<?= e(url('/admin/users/' . $user['id'])) ?>">Edit</a>
          <?php if ((int) $user['id'] !== (int) current_admin()['id']): ?>
            <form method="post" action="<?= e(url('/admin/users/' . $user['id'] . '/delete')) ?>" data-confirm="Delete <?= e($user['name']) ?>'s account?"><?= csrf_field() ?><button class="btn btn-sm btn-quiet" type="submit" aria-label="Delete"><?= icon('trash', 'icon icon-sm') ?></button></form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
