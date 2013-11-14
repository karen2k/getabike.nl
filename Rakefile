desc "Build out JS and HTML"
task :build do
  `jade --pretty src/*.jade --out www/`
  `coffee --compile --output www/js/ src/`
end

task :default => [ :build ]