guard 'coffeescript', input: 'src', output: 'www/js'
guard :shell do
  watch %r{^src/(.+\.jade)$} do | rel_path, filename |
    `jade #{rel_path} -P -o www/`
  end
end