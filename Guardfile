guard 'coffeescript', input: 'src', output: 'www'
guard :shell do
  watch %r{^src/(.+\.jade)$} do | rel_path, filename |
    `jade #{rel_path} -P -o www/`
  end
end