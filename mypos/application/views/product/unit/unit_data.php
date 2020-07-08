<section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Unit</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?= site_url('dashboard'); ?>"><i class="fas fa-fw fa-home"></i></a></li>
              <li class="breadcrumb-item active">Products</li>
              <li class="breadcrumb-item active"><a href="<?= site_url('unit'); ?>">Unit</a></li>
            </ol>
          </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <a href="<?= site_url('unit/add'); ?>" class="btn btn-primary mb-3"><i class="fas fa-fw fa-plus"></i> Add New Unit</a>
      <a href="<?= site_url('unit/import'); ?>" class="btn btn-success mb-3"><i class="fas fa-fw fa-file-upload"></i> Import Excel</a>
      <a href="<?= site_url('unit/deleteAll'); ?>" class="btn btn-danger mb-3" onclick="return confirm('Are You Sure Want to Delete All Units??')"><i class="fas fa-fw fa-trash"></i> Delete All</a>
      <?= $this->session->flashdata('message'); ?>
      <div class="table-responsive">
        <table class="table table-hover mt-3" id="pagination">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Name</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            foreach($row->result() as $key => $data) { ?>
            <tr>
              <td scope="row"><?= $no++; ?></td>
              <td scope="row"><?= $data->name; ?></td>
              <td scope="row">
                            <a href="<?= base_url('unit/edit/').$data->unit_id; ?>" class="badge badge-pill badge-success">update</a>
                            <a href="<?= base_url('unit/deleteunit/').$data->unit_id; ?>" onclick="return confirm('Are you sure want to delete <?= $data->name; ?> ?');" class="badge badge-pill badge-danger">delete</a>
            </tr>
          <?php }; ?>
          </tbody>
        </table>
      </div>

    </section>